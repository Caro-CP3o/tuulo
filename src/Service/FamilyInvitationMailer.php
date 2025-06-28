<?php
namespace App\Service;

use App\Entity\FamilyInvitation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

class FamilyInvitationMailer
{
    /**
     * Service responsible for sending family invitation emails to users or non-registered users.
     *
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     * @param \Twig\Environment $twig
     */
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {
    }

    /**
     * Send a family invitation email with a unique invitation code and URL.
     *
     * @param string $email
     * @param \App\Entity\FamilyInvitation $invitation
     * @param string $inviteUrl
     * @return void
     */
    public function sendInvitation(string $email, FamilyInvitation $invitation, string $inviteUrl): void
    {
        // ---------------------------
        // Fetch configuration values from environment
        // ---------------------------
        $senderEmail = $_ENV['MAILER_FROM'];
        $frontendUrl = $_ENV['FRONTEND_URL'];

        // ---------------------------
        // Render HTML email content from Twig template
        // ---------------------------
        $html = $this->twig->render('emails/invitation.html.twig', [
            'familyName' => $invitation->getFamily()->getName(),
            'invite_url' => $inviteUrl,
            'frontend_url' => $frontendUrl,
            'code' => $invitation->getCode(),
            'sender_email' => $senderEmail,
            'expires_at' => $invitation->getExpiresAt()->format('H:i'),

        ]);
        // ---------------------------
        // Create the email message with subject, sender, recipient, and HTML content
        // ---------------------------
        $message = (new Email())
            // ->from($inviteUrl)
            ->from($senderEmail)
            ->to($email)
            ->subject('Vous avez été invité à rejoindre une famille !')
            ->html($html); // 👈 Inject the rendered Twig HTML
        // ---------------------------
        // Send the email
        // ---------------------------
        $this->mailer->send($message);
    }
}