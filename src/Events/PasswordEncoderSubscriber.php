<?php

namespace App\Events;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\User;
use App\Service\EmailVerificationMailer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordEncoderSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UserPasswordHasherInterface $encoder,
        private EmailVerificationMailer $mailer
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

        // Hash the password
        $hash = $this->encoder->hashPassword($user, $user->getPassword());
        $user->setPassword($hash);

        // Generate 6-digit verification code
        // $code = random_int(100000, 999999);
        // $user->setEmailVerificationCode((string) $code);
        // $user->setIsVerified(false);

        if (!$user->isVerified()) {
            $code = random_int(100000, 999999);
            $user->setEmailVerificationCode((string) $code);
            $user->setIsVerified(false);

            // Send the email
            $this->mailer->sendVerificationCode($user);
        }
    }
}
