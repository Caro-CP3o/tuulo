<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\FamilyMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class FamilyAdminController extends AbstractController
{
    #[Route('/api/family/members/promote', name: 'api_family_member_promote', methods: ['POST'])]
    /**
     * Promotes a family member to family admin.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
     * @return JsonResponse
     */
    public function promoteMember(
        Request $request,
        EntityManagerInterface $em,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {
        // ---------------------------
        // Get the currently authenticated user
        // ---------------------------
        /** @var User $currentUser */
        $currentUser = $tokenStorage->getToken()?->getUser();

        if (!$currentUser || !$currentUser->isVerified()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }
        // ---------------------------
        // Validate request input
        // ---------------------------
        $memberId = $request->request->get('memberId');

        if (!$memberId) {
            return $this->json(['error' => 'Member ID required'], 400);
        }

        // ---------------------------
        // Check if current user is an active family admin
        // ---------------------------
        $currentFamilyMember = $currentUser->getFamilyMembers()->filter(fn($fm) => $fm->getStatus() === 'active')->first();
        if (!$currentFamilyMember || !in_array('ROLE_FAMILY_ADMIN', $currentUser->getRoles(), true)) {
            return $this->json(['error' => 'Only family admins can promote members'], 403);
        }
        // ---------------------------
        // Find the target family member
        // ---------------------------
        $member = $em->getRepository(FamilyMember::class)->find($memberId);

        if (!$member || $member->getFamily() !== $currentFamilyMember->getFamily()) {
            return $this->json(['error' => 'Member not found in your family'], 404);
        }

        // ---------------------------
        // Promote the member to family admin
        // ---------------------------
        $user = $member->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_FAMILY_ADMIN', $roles, true)) {
            return $this->json(['message' => 'User is already a family admin'], 200);
        }

        $user->setRoles(array_values(array_unique(array_merge($roles, ['ROLE_FAMILY_ADMIN']))));
        $em->persist($user);
        $em->flush();

        return $this->json(['message' => 'Member promoted to family admin'], 200);
    }
}
