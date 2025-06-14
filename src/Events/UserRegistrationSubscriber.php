<?php
namespace App\Events;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\FamilyMember;
use App\Entity\User;
use App\Repository\FamilyInvitationRepository;
use App\Service\EmailVerificationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationSubscriber implements EventSubscriberInterface
{
    // private LoggerInterface $logger;
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationMailer $mailer,
        private FamilyInvitationRepository $invitationRepo,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onUserRegister', EventPriorities::PRE_WRITE],
        ];
    }
    public function onUserRegister(ViewEvent $event): void
    {
        $user = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$user instanceof User || $method !== 'POST') {
            return;
        }

        $request = $event->getRequest();

        // Decode JSON body to extract invitationCode
        $data = json_decode($request->getContent(), true);
        $invitationCode = $data['invitationCode'] ?? null;

        if ($invitationCode) {
            $invitation = $this->invitationRepo->findOneBy(['code' => $invitationCode]);

            if (!$invitation || $invitation->isUsed() || $invitation->getExpiresAt() < new \DateTimeImmutable()) {
                throw new HttpException(400, 'Invalid or expired invitation code.');
            }

            $familyMember = new FamilyMember();
            $familyMember->setUser($user);
            $familyMember->setFamily($invitation->getFamily());
            $familyMember->setStatus('pending');

            $this->em->persist($familyMember);

            $invitation->setUsed(true);
            $this->em->persist($invitation);
            // Note: don't flush here unless you want to immediately commit; let API Platform do it.
        }

        try {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $code = bin2hex(random_bytes(16));
            $user->setEmailVerificationCode($code);
            $user->setIsVerified(false);
            $expiry = new \DateTime('+1 day');
            $user->setEmailVerificationExpiresAt($expiry);

            $this->mailer->sendVerificationCode($user, $code);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send verification email: ' . $e->getMessage());
            throw new HttpException(500, 'Failed to send verification email. Please try again later.');
        }
    }
    // public function onUserRegister(ViewEvent $event): void
    // {
    //     $user = $event->getControllerResult();
    //     $method = $event->getRequest()->getMethod();

    //     // Only act on POST requests and User entities
    //     if (!$user instanceof User || $method !== 'POST') {
    //         return;
    //     }
    //     error_log('UserRegistrationSubscriber triggered for user email: ' . $user->getEmail());
    //     $invitationCode = $event->getRequest()->get('invitationCode');

    //     if ($invitationCode) {
    //         $invitation = $this->invitationRepo->findOneBy(['code' => $invitationCode]);

    //         if (!$invitation || $invitation->isUsed() || $invitation->getExpiresAt() < new \DateTimeImmutable()) {
    //             throw new HttpException(400, 'Invalid or expired invitation code.');
    //         }

    //         // Link user to family with pending status
    //         $familyMember = new FamilyMember();
    //         $familyMember->setUser($user);
    //         $familyMember->setFamily($invitation->getFamily());
    //         $familyMember->setStatus('pending'); // or isApproved = false, depending on your schema

    //         $this->em->persist($familyMember);

    //         // Mark invitation as used
    //         $invitation->setUsed(true);
    //         $this->em->persist($invitation);
    //         $this->em->flush();
    //     }

    //     try {
    //         $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
    //         $user->setPassword($hashedPassword);

    //         // $code = (string) random_int(100000, 999999);
    //         $code = bin2hex(random_bytes(16));
    //         $user->setEmailVerificationCode($code);
    //         $user->setIsVerified(false);

    //         $expiry = new \DateTime('+1 day');
    //         $user->setEmailVerificationExpiresAt($expiry);

    //         $this->mailer->sendVerificationCode($user, $code);
    //     } catch (\Throwable $e) {
    //         $this->logger->error('Failed to send verification email: ' . $e->getMessage());
    //         throw new HttpException(500, 'Failed to send verification email. Please try again later.');
    //     }

    // }
}


// $invitation = $invitationRepository->findOneBy(['code' => $submittedCode]);
// if (!$invitation || $invitation->isUsed() || $invitation->getExpiresAt() < new \DateTimeImmutable()) {
//     throw new \Exception("Invitation invalide ou expirée");
// }

// // Proceed with creating User entity
// $user->setIsApproved(false); // or status = 'pending'

// // Optionally associate to family now or wait until approval
// $familyMember = new FamilyMember();
// $familyMember->setUser($user);
// $familyMember->setFamily($invitation->getFamily());
// $entityManager->persist($familyMember);

// // Mark the invitation as used
// $invitation->setUsed(true);