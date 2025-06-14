<?php

namespace App\Controller;

use App\Entity\FamilyMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
// use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\SecurityBundle\Security;

final class FamilyMemberController extends AbstractController
{
    // #[Route('/family/member', name: 'app_family_member')]
    // public function index(): Response
    // {
    //     return $this->render('family_member/index.html.twig', [
    //         'controller_name' => 'FamilyMemberController',
    //     ]);
    // }
    #[Route('/api/family_members/{id}/approve', name: 'family_member_approve', methods: ['PATCH'])]
    public function approve(
        FamilyMember $member,
        Security $security,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $currentUser = $security->getUser();

        if (!in_array('ROLE_FAMILY_ADMIN', $currentUser->getRoles(), true)) {
            return new JsonResponse(['error' => 'Only admins can approve members.'], 403);
        }

        $family = $member->getFamily();

        if ($member->getStatus() === 'active') {
            return new JsonResponse(['message' => 'Member is already active.'], 200);
        }

        $isAdminOfFamily = false;

        foreach ($currentUser->getFamilyMembers() as $fm) {

            if (
                $fm->getFamily()->getId() === $family->getId() &&
                $fm->getStatus() === 'active'
            ) {
                $isAdminOfFamily = true;
                break;
            }
        }

        if (!$isAdminOfFamily) {
            return new JsonResponse(['error' => 'You are not an active member of this family.'], 403);
        }

        $member->setStatus('active');
        $em->flush();

        // ✅ Send notification email to the user
        $invitedUser = $member->getUser();
        if ($invitedUser && $invitedUser->getEmail()) {
            $email = (new Email())
                ->from('noreply@yourapp.com')
                ->to($invitedUser->getEmail())
                ->subject('You have been approved!')
                ->text("Hello, your request to join the family '{$family->getName()}' has been approved. You can now log in.");

            $mailer->send($email);
        }

        return new JsonResponse(['message' => 'Family member approved.']);
    }

    #[Route('/api/family_members/pending', name: 'family_member_pending', methods: ['GET'])]
    public function listPendingRequests(Security $security, EntityManagerInterface $em): JsonResponse
    {
        $user = $security->getUser();

        if (!in_array('ROLE_FAMILY_ADMIN', $user->getRoles(), true)) {
            return new JsonResponse(['error' => 'Only admins can view pending requests.'], 403);
        }

        $familyIds = [];
        foreach ($user->getFamilyMembers() as $fm) {
            if ($fm->getStatus() === 'active') {
                $familyIds[] = $fm->getFamily()->getId();
            }
        }

        if (empty($familyIds)) {
            return new JsonResponse([]);
        }

        $pendingMembers = $em->createQueryBuilder()
            ->select('fm', 'u')
            ->from(FamilyMember::class, 'fm')
            ->join('fm.user', 'u')
            ->where('fm.family IN (:familyIds)')
            ->andWhere('fm.status = :status')
            ->setParameter('familyIds', $familyIds)
            ->setParameter('status', 'pending')
            ->getQuery()
            ->getArrayResult();

        $formatted = array_map(function ($member) {
            return [
                'id' => $member['id'],
                'email' => $member['user']['email'] ?? null,
            ];
        }, $pendingMembers);

        return new JsonResponse($formatted);
    }

    // #[Route('/api/family_members/pending', name: 'family_member_pending', methods: ['GET'])]
    // public function listPendingRequests(Security $security): JsonResponse
    // {
    //     $user = $security->getUser();

    //     if (!in_array('ROLE_FAMILY_ADMIN', $user->getRoles(), true)) {
    //         return new JsonResponse(['error' => 'Only admins can view pending requests.'], 403);
    //     }

    //     $familyIds = [];
    //     foreach ($user->getFamilyMembers() as $fm) {
    //         if ($fm->getStatus() === 'active') {
    //             $familyIds[] = $fm->getFamily()->getId();
    //         }
    //     }

    //     if (empty($familyIds)) {
    //         return new JsonResponse([]);
    //     }

    //     $pendingMembers = $this->getDoctrine()
    //         ->getRepository(FamilyMember::class)
    //         ->createQueryBuilder('fm')
    //         ->join('fm.user', 'u')
    //         ->where('fm.family IN (:familyIds)')
    //         ->andWhere('fm.status = :status')
    //         ->setParameter('familyIds', $familyIds)
    //         ->setParameter('status', 'pending')
    //         ->getQuery()
    //         ->getArrayResult();

    //     $formatted = array_map(function ($member) {
    //         return [
    //             'id' => $member['id'],
    //             'email' => $member['user']['email'] ?? null,
    //         ];
    //     }, $pendingMembers);

    //     return new JsonResponse($formatted);
    // }

}
