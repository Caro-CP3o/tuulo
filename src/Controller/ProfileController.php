<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FamilyMemberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ProfileController extends AbstractController
{
    #[Route('/api/profile', name: 'app_profile', methods: ['GET'])]
    /**
     * Fetch and return the authenticated user's profile.
     * 
     * - Retrieve current user from security context.
     * - Return user data as JSON (using 'user:read' serialization group).
     * 
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @return JsonResponse
     */
    public function profile(Security $security): JsonResponse
    {
        $user = $security->getUser();

        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }

    #[Route('/api/profile', name: 'app_delete_me', methods: ['DELETE'])]
    /**
     * Delete the authenticated user's account.
     * 
     * - Check if user is the only admin in the family.
     * - Prevent deletion if user is sole admin.
     * - Otherwise, remove user from database.
     * 
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @return JsonResponse
     */
    public function delete(
        Security $security,
        EntityManagerInterface $em
    ): JsonResponse {
        /** @var User $user */
        $user = $security->getUser();

        // --------------------------------------------------------
        // 1. Get user's family membership and family (if any)
        // --------------------------------------------------------
        $familyMember = $user->getFamilyMembers()->first() ?: null;
        $family = $familyMember?->getFamily();

        if ($family) {
            // --------------------------------------------------------
            // 2. Check if user is the only admin in the family
            // --------------------------------------------------------
            $adminUsers = $family->getFamilyMembers()->filter(function ($member) {
                return in_array('ROLE_FAMILY_ADMIN', $member->getUser()->getRoles(), true);
            });

            $isCurrentUserAdmin = in_array('ROLE_FAMILY_ADMIN', $user->getRoles(), true);

            if ($isCurrentUserAdmin && $adminUsers->count() === 1) {
                return new JsonResponse([
                    'error' => 'Vous ne pouvez pas supprimer votre compte car vous êtes le seul administrateur de votre famille. Veuillez transférer la propriété de votre famille à un autre membre avant de supprimer votre compte.'
                ], Response::HTTP_FORBIDDEN);
            }
        }
        // --------------------------------------------------------
        // 3. Remove user from database
        // --------------------------------------------------------
        $em->remove($user);
        $em->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}