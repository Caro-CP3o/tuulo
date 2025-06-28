<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\FamilyMember;
use App\Repository\FamilyInvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Controller for joining an existing family using an invitation code.
 * 
 * Flow:
 * - User submits a code.
 * - Validate code, check if expired or already used.
 * - Ensure user isn’t already a member or has a pending request.
 * - Add user as pending family member and mark invitation as used.
 * - Adjust user color if duplicate.
 * - Return success response.
 */
final class JoinFamilyController extends AbstractController
{
    #[Route('/api/join-family', name: 'join_family', methods: ['POST'])]
    /**
     * Handle a user request to join a family with an invitation code.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \App\Repository\FamilyInvitationRepository $invitationRepo
     * @return JsonResponse
     */
    public function __invoke(
        Request $request,
        Security $security,
        EntityManagerInterface $em,
        FamilyInvitationRepository $invitationRepo
    ): JsonResponse {
        // ----------------------------------------
        // 1. Check user authentication
        // ----------------------------------------
        $user = $security->getUser();

        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // ----------------------------------------
        // 2. Parse and validate invitation code
        // -----------------------------------------
        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? null;

        if (!$code) {
            return new JsonResponse(['error' => 'Invitation code is required'], 400);
        }

        $invitation = $invitationRepo->findOneBy(['code' => $code]);

        if (!$invitation || $invitation->isExpired()) {
            return new JsonResponse(['error' => 'Invalid or expired invitation code'], 400);
        }
        // ----------------------------------------
        // 3. Check if user is already in this family
        // ----------------------------------------
        $family = $invitation->getFamily();

        $existing = $em->getRepository(FamilyMember::class)->findOneBy([
            'user' => $user,
            'family' => $family,
        ]);

        if ($existing) {
            return new JsonResponse(['error' => 'You have already requested to join this family.'], 400);
        }

        // ----------------------------------------
        // 4. Create pending family member & mark invitation as used
        // ----------------------------------------
        $member = new FamilyMember();
        $member->setUser($user);
        $member->setFamily($family);
        $member->setStatus('pending');

        if ($invitation->isUsed()) {
            return new JsonResponse(['error' => 'Invitation code has already been used'], 400);
        }
        $em->persist($member);
        $invitation->setUsed(true);

        // ----------------------------------------
        // 5. Adjust user's color if already used in the family
        // ----------------------------------------
        $usedColors = array_map(
            fn(FamilyMember $fm) => $fm->getUser()->getColor(),
            $family->getFamilyMembers()->toArray()
        );

        if (in_array($user->getColor(), $usedColors)) {
            $originalColor = $user->getColor();
            $newColor = $this->generateSimilarUniqueColor($originalColor, $usedColors);
            $user->setColor($newColor);
        }

        $em->flush();

        return new JsonResponse([
            'message' => 'Join request sent. Awaiting admin approval.',
            'familyId' => $family->getId(),
        ], 201);
    }
    /**
     * Generate a color similar to the base color that is not already used.
     *
     * @param string $baseColor
     * @param array $usedColors
     * @return string
     */
    private function generateSimilarUniqueColor(string $baseColor, array $usedColors): string
    {
        // Convert hex to RGB
        list($r, $g, $b) = sscanf($baseColor, "#%02x%02x%02x");

        for ($i = 0; $i < 20; $i++) {
            // Adjust RGB values (±20)
            $nr = max(0, min(255, $r + random_int(-20, 20)));
            $ng = max(0, min(255, $g + random_int(-20, 20)));
            $nb = max(0, min(255, $b + random_int(-20, 20)));

            $newHex = sprintf("#%02x%02x%02x", $nr, $ng, $nb);

            if (!in_array($newHex, $usedColors)) {
                return $newHex;
            }
        }

        // Fallback to a completely random color
        do {
            $fallback = sprintf("#%02x%02x%02x", random_int(0, 255), random_int(0, 255), random_int(0, 255));
        } while (in_array($fallback, $usedColors));

        return $fallback;
    }

}
