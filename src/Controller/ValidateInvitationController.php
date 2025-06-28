<?php


namespace App\Controller;

use App\Repository\FamilyInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class ValidateInvitationController extends AbstractController
{
    #[Route('/api/validate-invitation', name: 'validate_invitation', methods: ['GET'])]
    /**
     * Validate an invitation code and return related family data.
     * 
     * - Get 'code' from query parameters.
     * - Check if code exists and is valid.
     * - Return family info if valid.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Repository\FamilyInvitationRepository $repo
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @return JsonResponse
     */
    public function validate(
        Request $request,
        FamilyInvitationRepository $repo,
        EntityManagerInterface $em
    ): JsonResponse {
        // --------------------------------------------------------
        // 1. Get 'code' from query parameters
        // --------------------------------------------------------
        $code = $request->query->get('code');

        if (!$code) {
            return new JsonResponse(['error' => 'Missing code'], 400);
        }
        // --------------------------------------------------------
        // 2. Find invitation by code
        // --------------------------------------------------------
        $invitation = $repo->findOneBy(['code' => $code]);

        if (!$invitation) {
            return new JsonResponse(['error' => 'Invitation not found'], 404);
        }
        // --------------------------------------------------------
        // 3. Check if invitation is expired
        // --------------------------------------------------------
        if (method_exists($invitation, 'isExpired') && $invitation->isExpired()) {
            return new JsonResponse(['error' => 'Expired invitation'], 400);
        }

        // --------------------------------------------------------
        // 4. Return success response with family info
        // --------------------------------------------------------
        $family = $invitation->getFamily();

        return new JsonResponse([
            'message' => 'Valid code',
            'family' => [
                'id' => $family->getId(),
                'name' => $family->getName(),
            ]
        ], 200);
    }
}