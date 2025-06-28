<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Twig\Environment;

class EmailVerificationMailer
{
    private MailerInterface $mailer;
    private LoggerInterface $logger;

    /**
     * Service responsible for sending email verification codes to users.
     *
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Twig\Environment $twig
     */
    public function __construct(MailerInterface $mailer, LoggerInterface $logger, private Environment $twig)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
    }

    /**
     * Send a verification email to the given user.
     *
     * @param \App\Entity\User $user
     * @param string $action
     * @return void
     */
    public function sendVerificationCode(User $user, string $action): void
    {
        // ---------------------------
        // Build verification URL and email metadata
        // ---------------------------
        $backendUrl = $_ENV['BACKEND_URL'];
        $code = $user->getEmailVerificationCode();
        $verificationLink = $backendUrl . '/verify-email?code=' . urlencode($code);
        $senderEmail = $_ENV['MAILER_FROM'];
        // ---------------------------
        // Render HTML content from Twig template
        // ---------------------------
        $html = $this->twig->render('emails/verification.html.twig', [
            'user_name' => $user->getFirstname(),
            'frontend_url' => $backendUrl,
            'verification_link' => $verificationLink,
            'expires_at' => $user->getEmailVerificationExpiresAt()->format('H:i'),
        ]);
        // ---------------------------
        // Create the email object with subject, recipient, sender, and content
        // ---------------------------
        $email = (new Email())
            ->from($senderEmail)
            ->to($user->getEmail())
            ->subject('Vérifiez votre adresse e-mail')
            ->html($html);
        // ---------------------------
        // Send the email and log the action
        // ---------------------------
        $this->mailer->send($email);
        $this->logger->info("Sent verification email to {$user->getEmail()} with code: {$code}");
    }
}
