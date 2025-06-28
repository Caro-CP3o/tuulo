<?php

namespace App\Events;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\FamilyMember;
use App\Entity\User;
use App\Entity\MediaObject;
use App\Repository\FamilyInvitationRepository;
use App\Service\EmailVerificationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Psr\Log\LoggerInterface;

class UserRegistrationSubscriber implements EventSubscriberInterface
{
    /**
     * Temporary store of invitation codes indexed by user object hash.
     *
     * @var array<string, string>
     */
    private array $invitationCodes = [];
    /**
     * Summary of __construct
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher
     * @param \App\Service\EmailVerificationMailer $mailer
     * @param \App\Repository\FamilyInvitationRepository $invitationRepo
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationMailer $mailer,
        private FamilyInvitationRepository $invitationRepo,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {
    }

    /**
     * Summary of getSubscribedEvents
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => [
                ['handlePreWrite', EventPriorities::PRE_WRITE],
                ['handlePostWrite', EventPriorities::POST_WRITE],
            ],
        ];
    }

    /**
     * Pre-write event handler: runs before User is saved.
     * Responsible for hashing password, generating verification code, 
     * extracting invitation code, and sending verification email.
     *
     * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @return void
     */
    public function handlePreWrite(ViewEvent $event): void
    {
        // ---------------------------
        // Retrieve the entity from the controller result.
        // ---------------------------
        $user = $event->getControllerResult();
        $request = $event->getRequest();

        // ---------------------------
        // Only proceed if the entity is a User and HTTP method is POST (registration).
        // ---------------------------
        if (!$user instanceof User || $request->getMethod() !== 'POST') {
            return;
        }
        // ---------------------------
        // Hash the plain password provided by the user and set it.
        // ---------------------------
        $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword()));
        // ---------------------------
        // Mark user as unverified and generate a new email verification code with expiry.
        // ---------------------------
        $user->setIsVerified(false);
        $code = bin2hex(random_bytes(16));
        $user->setEmailVerificationCode($code);
        $user->setEmailVerificationExpiresAt(new \DateTime('+1 day'));
        // ---------------------------
        // Extract invitation code from the request:
        // Support both JSON (API clients) and form-data (multipart upload for avatar).
        // ---------------------------
        $invitationCode = null;
        $contentType = $request->headers->get('Content-Type');

        if (str_contains($contentType, 'application/json')) {
            $data = json_decode($request->getContent(), true);
            $invitationCode = $data['invitationCode'] ?? null;
        } else {
            $invitationCode = $request->request->get('invitationCode');
        }
        // ---------------------------
        // Store invitation code temporarily keyed by user hash
        // so we can process it after persistence.
        // ---------------------------
        if ($invitationCode) {
            $this->invitationCodes[spl_object_hash($user)] = $invitationCode;
        }

        // ---------------------------
        // Attempt to send the verification email with the generated code.
        // If sending fails, log the error and throw an HTTP 500 error.
        // ---------------------------
        try {
            $this->mailer->sendVerificationCode($user, $code);
        } catch (\Throwable $e) {
            $this->logger->error('Email sending failed: ' . $e->getMessage());
            throw new HttpException(500, 'Unable to send verification email.');
        }
    }

    /**
     * Post-write event handler: runs after User is saved.
     * Responsible for linking the user to a family if an invitation code
     * was provided, and handling avatar file upload.
     *
     * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     * @return void
     */
    public function handlePostWrite(ViewEvent $event): void
    {
        // ---------------------------
        // Retrieve the entity from the controller result.
        // ---------------------------
        $user = $event->getControllerResult();
        if (!$user instanceof User) {
            return;
        }
        // ---------------------------
        // Retrieve the unique hash key for this user to get stored invitation code.
        // ---------------------------
        $userHash = spl_object_hash($user);
        // ---------------------------
        // Get the current HTTP request to check for avatar upload.
        // ---------------------------
        $request = $this->requestStack->getCurrentRequest();

        // ---------------------------
        // Check if an invitation code was provided during registration.
        // ---------------------------
        $invitationCode = $this->invitationCodes[$userHash] ?? null;
        if ($invitationCode) {
            // ---------------------------
            // Find the FamilyInvitation entity matching the code.
            // ---------------------------
            $invitation = $this->invitationRepo->findOneBy(['code' => $invitationCode]);
            // ---------------------------
            // Validate that invitation exists, is unused, and not expired.
            // Throw 400 error if invalid.
            // ---------------------------
            if (!$invitation || $invitation->isUsed() || $invitation->getExpiresAt() < new \DateTimeImmutable()) {
                throw new HttpException(400, 'Invalid or expired invitation code.');
            }
            // ---------------------------
            // Create a new FamilyMember entity linking user and family.
            // Status is set to 'pending' awaiting approval.
            // ---------------------------
            $family = $invitation->getFamily();
            $familyMember = new FamilyMember();
            $familyMember->setUser($user);
            $familyMember->setFamily($family);
            $familyMember->setStatus('pending');
            // ---------------------------
            // Mark invitation as used to prevent reuse.
            // ---------------------------
            $invitation->setUsed(true);
            // ---------------------------
            // Persist both new FamilyMember and updated invitation.
            // ---------------------------
            $this->em->persist($familyMember);
            $this->em->persist($invitation);
        }

        // ---------------------------
        // Handle avatar upload from multipart/form-data request.
        // If an avatar file was uploaded, create a MediaObject and link it to user.
        // ---------------------------
        /** @var UploadedFile|null $avatarFile */
        $avatarFile = $request->files->get('avatar');
        if ($avatarFile instanceof UploadedFile) {
            $mediaObject = new MediaObject();
            $mediaObject->setFile($avatarFile);
            $mediaObject->setUser($user);

            $this->em->persist($mediaObject);
            $user->setAvatar($mediaObject);
        }

        // ---------------------------
        // Persist changes to the database.
        // ---------------------------
        $this->em->flush();

        // ---------------------------
        // Remove the stored invitation code for this user since processing is done.
        // ---------------------------
        unset($this->invitationCodes[$userHash]);
    }
}