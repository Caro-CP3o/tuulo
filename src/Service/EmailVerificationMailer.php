<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EmailVerificationMailer
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    public function sendVerificationCode(User $user, string $action): void
    {
        $code = $user->getEmailVerificationCode();
        $verificationLink = sprintf(
            'http://localhost:8000/verify-email?code=%s&action=%s',
            urlencode($code),
            urlencode($action)
        );
        // $verificationLink = sprintf('http://localhost:8000/verify-email?code=%s', $code);

        $email = (new Email())
            ->from('no-reply@tuulo.com')
            ->to($user->getEmail())
            ->subject('Verify your email address')
            ->text(
                sprintf("Hello %s,\n\nPlease verify your email by clicking the link below:\n%s", $user->getFirstname(), $verificationLink)
            );

        $this->mailer->send($email);
        // $this->logger->info("Sent verification email to {$user->getEmail()} with code: {$code}");
        $this->logger->info("Sent verification email to {$user->getEmail()} with code: {$code} and action: {$action}");
    }
}
