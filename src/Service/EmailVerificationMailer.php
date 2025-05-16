<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailVerificationMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    public function sendVerificationCode(User $user): void
    {
        $email = (new Email())
            ->from('no-reply@tuulo.com')
            ->to($user->getEmail())
            ->subject('Verify your email address')
            ->text("Hello " . $user->getFirstname() . "!\n\nYour Tuulo verification code is: " . $user->getEmailVerificationCode());

        $this->mailer->send($email);

        // Log the action
        $this->logger->info("Sent verification email to {$user->getEmail()}");
    }
}
