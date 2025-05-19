<?php

// namespace App\Controller;

// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Routing\Attribute\Route;

// final class ApproveJoinRequestController extends AbstractController
// {
//     #[Route('/approve/join/request', name: 'app_approve_join_request')]
//     public function index(): Response
//     {
//         return $this->render('approve_join_request/index.html.twig', [
//             'controller_name' => 'ApproveJoinRequestController',
//         ]);
//     }
// }

namespace App\Controller;

use App\Repository\FamilyMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;

final class ApproveJoinRequestController extends AbstractController
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    #[Route('/api/family/approve', name: 'family_approve_request', methods: ['GET'])]
    public function approve(Request $request, FamilyMemberRepository $memberRepo, EntityManagerInterface $em, MailerInterface $mailer): JsonResponse
    {
        $token = $request->query->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Missing token.'], 400);
        }

        $member = $memberRepo->findOneBy(['token' => $token]);

        if (!$member) {
            return new JsonResponse(['error' => 'Invalid token.'], 404);
        }

        $member->setStatus('approved');
        $em->flush();

        // Send registration link to invitee
        $baseUrl = rtrim($this->params->get('app.frontend_url'), '/');
        $registrationLink = $baseUrl . '/register?token=' . $token;
        $email = $member->getEmail();

        $emailMessage = (new Email())
            ->from('noreply@tuulo.be')
            ->to($email)
            ->subject('Complete Your Tuulo Registration')
            ->html("<p>Your request has been approved! <a href='{$registrationLink}'>Click here to register</a>.</p>");

        $mailer->send($emailMessage);

        return new JsonResponse(['message' => 'Invitation approved and registration link sent.']);
    }
}
