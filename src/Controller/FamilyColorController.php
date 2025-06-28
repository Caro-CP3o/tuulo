<?php

namespace App\Controller;

use App\Repository\FamilyInvitationRepository;
use App\Repository\FamilyMemberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class FamilyColorController extends AbstractController
{
    #[Route('/api/families/colors-used', name: 'get_family_colors_used', methods: ['GET'])]
    /**
     * Returns a list of colors already used by family members for the given invitation code.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Repository\FamilyInvitationRepository $invitationRepo
     * @param \App\Repository\FamilyMemberRepository $memberRepo
     * @return JsonResponse
     */
    public function getUsedColors(
        Request $request,
        FamilyInvitationRepository $invitationRepo,
        FamilyMemberRepository $memberRepo
    ): JsonResponse {
        // ---------------------------
        // Validate and extract invitation code from query
        // ---------------------------
        $code = $request->query->get('code');
        if (!$code) {
            return new JsonResponse(['error' => 'No invitation code provided'], 400);
        }
        // ---------------------------
        // Find the invitation and check if it is valid
        // ---------------------------
        $invitation = $invitationRepo->findOneBy(['code' => $code]);
        if (!$invitation || !$invitation->isValid()) {
            return new JsonResponse(['error' => 'Invalid or expired invitation code'], 400);
        }
        // ---------------------------
        // Retrieve the family linked to this invitation
        // ---------------------------
        $family = $invitation->getFamily();
        if (!$family) {
            return new JsonResponse(['error' => 'Invitation has no linked family'], 400);
        }

        // ---------------------------
        // Fetch all family members and extract their colors
        // ---------------------------
        $familyMembers = $memberRepo->findBy(['family' => $family]);

        // Extract colors, filtering out null or empty colors
        $colors = array_filter(array_map(fn($member) => $member->getColor(), $familyMembers));

        // ---------------------------
        // Remove duplicate colors and return
        // ---------------------------
        $uniqueColors = array_values(array_unique($colors));

        return new JsonResponse(['usedColors' => $uniqueColors]);
    }
}