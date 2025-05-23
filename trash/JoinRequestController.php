<?php

namespace App\Controller;

use App\Entity\FamilyMember;
use App\Repository\FamilyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;

final class JoinRequestController extends AbstractController
{
    private ParameterBagInterface $params;
    private LoggerInterface $logger;

    public function __construct(ParameterBagInterface $params, LoggerInterface $logger)
    {
        $this->params = $params;
        $this->logger = $logger;

    }

    #[Route('/api/family/join-request', name: 'family_join_request', methods: ['POST'])]
    public function joinRequest(Request $request, FamilyRepository $familyRepo, EntityManagerInterface $em, MailerInterface $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $familyCode = $data['familyCode'] ?? null;
        $email = $data['email'] ?? null;

        if (!$familyCode || !$email) {
            return new JsonResponse(['error' => 'Missing family code or email.'], 400);
        }

        $family = $familyRepo->findOneBy(['joinCode' => $familyCode]);

        if (!$family) {
            return new JsonResponse(['error' => 'Family not found.'], 404);
        }

        $existing = $family->getFamilyMembers()->filter(
            fn($m) =>
            $m->getEmail() === $email && $m->getStatus() === 'pending'
        );

        if (count($existing) > 0) {
            return new JsonResponse(['error' => 'An invite is already pending for this email.'], 409);
        }

        $token = bin2hex(random_bytes(16));

        $member = new FamilyMember();
        $member->setEmail($email);
        $member->setFamily($family);
        $member->setStatus('pending');
        $member->setToken($token);

        $em->persist($member);
        $em->flush();


        $baseUrl = rtrim($this->params->get('app.frontend_url'), '/');

        $admins = $family->getFamilyMembers()->filter(function ($member) {
            return $member->getStatus() === 'active'
                && $member->getUser() !== null
                && in_array('ROLE_FAMILY_ADMIN', $member->getUser()->getRoles(), true);
        });
        foreach ($admins as $adminMember) {
            $adminUser = $adminMember->getUser();
            $adminEmail = $adminUser->getEmail();

            $emailMessage = (new Email())
                ->from('noreply@tuulo.be')
                ->to($adminEmail)
                ->subject('New Tuulo Join Request')
                ->html("<p>A new member has requested to join the family: {$family->getName()}<br>
                Email: {$email}<br>
                <a href='{$baseUrl}/api/family/approve?token={$token}'>Click here to approve</a></p>");

            try {
                $mailer->send($emailMessage);
            } catch (\Throwable $e) {
                $this->logger->error('Failed to send join request email to admin: ' . $e->getMessage());
            }
        }
        return new JsonResponse(['message' => 'Join request submitted.']);
    }
}
