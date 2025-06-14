<?php

namespace App\Controller;

use App\Repository\FamilyInvitationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ValidateInvitationController extends AbstractController
{
    // #[Route('/api/validate-invitation', name: 'validate_invitation', methods: ['GET'])]
    // public function validate(Request $request, FamilyInvitationRepository $repo): JsonResponse
    // {
    //     $code = $request->query->get('code');
    //     $invitation = $repo->findOneBy(['code' => $code]);

    //     if (!$invitation || $invitation->isExpired()) {
    //         return new JsonResponse(['error' => 'Invalid or expired code'], 400);
    //     }

    //     return new JsonResponse(['message' => 'Valid code'], 200);
    // }
    #[Route('/api/validate-invitation', name: 'validate_invitation', methods: ['GET'])]
    public function validate(Request $request, FamilyInvitationRepository $repo): JsonResponse
    {
        $code = $request->query->get('code');

        if (!$code) {
            return new JsonResponse(['error' => 'Missing code'], 400);
        }

        $invitation = $repo->findOneBy(['code' => $code]);

        if (!$invitation) {
            return new JsonResponse(['error' => 'Invitation not found'], 404);
        }

        if (method_exists($invitation, 'isExpired') && $invitation->isExpired()) {
            return new JsonResponse(['error' => 'Expired invitation'], 400);
        }

        return new JsonResponse(['message' => 'Valid code'], 200);
    }
}
