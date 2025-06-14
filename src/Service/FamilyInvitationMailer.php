<?php

namespace App\Service;

use App\Entity\FamilyInvitation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class FamilyInvitationMailer
{
    public function __construct(private MailerInterface $mailer)
    {
    }

    public function sendInvitation(string $email, FamilyInvitation $invitation, string $inviteUrl): void
    {
        $message = (new Email())
            ->to($email)
            ->subject('You’ve been invited to join a family')
            ->html(sprintf(
                '<p>You’ve been invited to join a family. Click the link below to register:</p><p><a href="%s">%s</a></p>',
                htmlspecialchars($inviteUrl),
                htmlspecialchars($inviteUrl)
            ));

        $this->mailer->send($message);
    }
}



// namespace App\Service;

// use App\Entity\FamilyInvitation;
// use Symfony\Component\Mailer\MailerInterface;
// use Symfony\Component\Mime\Email;

// class FamilyInvitationMailer
// {
//     public function __construct(private MailerInterface $mailer)
//     {
//     }

//     public function sendInvitation(string $recipientEmail, FamilyInvitation $invitation): void
//     {
//         $url = sprintf('https://your-frontend-app.com/invite/%s', $invitation->getCode());

//         $email = (new Email())
//             ->from('noreply@yourapp.com')
//             ->to($recipientEmail)
//             ->subject('You are invited to join a family on Tuulo!')
//             ->text("Hello!\n\nYou’ve been invited to join a family on Tuulo.\nUse this link: $url\n\nThis link expires in 7 days.");

//         $this->mailer->send($email);
//     }
// }
