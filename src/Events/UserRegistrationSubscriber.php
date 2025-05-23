<?php
namespace App\Events;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\EmailVerificationMailer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRegistrationSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerificationMailer $mailer,
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

        // Only act on POST requests and User entities
        if (!$user instanceof User || $method !== 'POST') {
            return;
        }
        error_log('UserRegistrationSubscriber triggered for user email: ' . $user->getEmail());

        try {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);

            $code = (string) random_int(100000, 999999);
            $user->setEmailVerificationCode($code);
            $user->setIsVerified(false);

            $this->mailer->sendVerificationCode($user, $code);
        } catch (\Throwable $e) {
            error_log('Error in UserRegistrationSubscriber: ' . $e->getMessage());
            throw $e;
        }
    }
}
