<?php

namespace App\Controller;

use App\Entity\FamilyMember;
use App\Repository\FamilyMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Serializer\SerializerInterface;
use Twig\Environment;

final class FamilyMemberController extends AbstractController
{
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {
    }

    #[Route('/api/family_members/by_family/{familyId}', name: 'family_members_by_family', methods: ['GET'])]
    /**
     * Get active members of a family, including user info.
     *
     * @param int $familyId
     * @param \App\Repository\FamilyMemberRepository $familyMemberRepository
     * @param \Symfony\Component\Serializer\SerializerInterface $serializer
     * @return JsonResponse
     */
    public function getMembersByFamily(
        int $familyId,
        FamilyMemberRepository $familyMemberRepository,
        SerializerInterface $serializer
    ): JsonResponse {

        // ---------------------------
        // Fetch active family members with their linked user
        // ---------------------------
        $members = $familyMemberRepository->createQueryBuilder('fm')
            ->leftJoin('fm.user', 'u')
            ->addSelect('u')
            ->where('fm.family = :familyId')
            ->andWhere('fm.status = :status')
            ->setParameter('familyId', $familyId)
            ->setParameter('status', 'active')
            ->getQuery()
            ->getResult();

        // Serialize for JSON response
        $json = $serializer->serialize(
            $members,
            'json',
            ['groups' => ['family_member:read', 'user:read']]
        );

        return new JsonResponse($json, 200, [], true);
    }

    #[Route('/api/family_members/{id}/approve', name: 'family_member_approve', methods: ['PATCH'])]
    /**
     * Approve a pending family member (only by active family admin).
     *
     * @param \App\Entity\FamilyMember $member
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     * @return JsonResponse
     */
    public function approve(
        FamilyMember $member,
        Security $security,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        Environment $twig
    ): JsonResponse {

        // ---------------------------
        // Get current user and check if has a role admin
        // ---------------------------
        $currentUser = $security->getUser();

        if (!in_array('ROLE_FAMILY_ADMIN', $currentUser->getRoles(), true)) {
            return new JsonResponse(['error' => 'Only admins can approve members.'], 403);
        }

        $family = $member->getFamily();

        if ($member->getStatus() === 'active') {
            return new JsonResponse(['message' => 'Member is already active.'], 200);
        }
        // ---------------------------
        // Check if current user is active member of the family
        // ---------------------------
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
        // ---------------------------
        // Approve family member
        // ---------------------------
        $member->setStatus('active');
        $em->flush();


        // ---------------------------
        // Send email notification
        // ---------------------------
        $invitedUser = $member->getUser();
        $senderEmail = $_ENV['MAILER_FROM'];
        $frontendUrl = $_ENV['FRONTEND_URL'];

        $html = $this->twig->render('emails/approved.html.twig', [
            'familyName' => $family->getName(),
            'sender_email' => $senderEmail,
            'frontend_url' => $frontendUrl,
        ]);
        if ($invitedUser && $invitedUser->getEmail()) {
            $email = (new Email())
                ->from($senderEmail)
                ->to($invitedUser->getEmail())
                ->subject('Vous avez été approuvé pour rejoindre une famille !')
                ->html($html);

            $mailer->send($email);
        }

        return new JsonResponse(['message' => 'Family member approved.']);
    }

    #[Route('/api/family_members/pending', name: 'family_member_pending', methods: ['GET'])]
    /**
     * List pending membership requests for families the current admin belongs to.
     *
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @return JsonResponse
     */
    public function listPendingRequests(Security $security, EntityManagerInterface $em): JsonResponse
    {
        $user = $security->getUser();

        if (!in_array('ROLE_FAMILY_ADMIN', $user->getRoles(), true)) {
            return new JsonResponse(['error' => 'Only admins can view pending requests.'], 403);
        }

        // ---------------------------
        // Find active family Id's where the user is an active member
        // ---------------------------
        $familyIds = [];
        foreach ($user->getFamilyMembers() as $fm) {
            if ($fm->getStatus() === 'active') {
                $familyIds[] = $fm->getFamily()->getId();
            }
        }

        if (empty($familyIds)) {
            return new JsonResponse([]);
        }

        // ---------------------------
        // Fentch peending members
        // ---------------------------
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

        // Formt response
        $formatted = array_map(function ($member) {
            return [
                'id' => $member['id'],
                'email' => $member['user']['email'] ?? null,
            ];
        }, $pendingMembers);

        return new JsonResponse($formatted);
    }




    #[Route('/api/family_members/{id}/reject', name: 'family_member_reject', methods: ['PATCH'])]
    /**
     * Reject a pending family member request (only by active family admin).
     *
     * @param \App\Entity\FamilyMember $member
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     * @return JsonResponse
     */
    public function reject(
        FamilyMember $member,
        Security $security,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $currentUser = $security->getUser();

        if (!in_array('ROLE_FAMILY_ADMIN', $currentUser->getRoles(), true)) {
            return new JsonResponse(['error' => 'Only admins can reject members.'], 403);
        }

        $family = $member->getFamily();
        // ---------------------------
        // Check if the current user is an active member of the family
        // ---------------------------
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

        if ($member->getStatus() !== 'pending') {
            return new JsonResponse(['message' => 'Only pending requests can be rejected.'], 400);
        }
        // ---------------------------
        // Reject membership
        // ---------------------------
        $member->setStatus('rejected');
        $em->flush();

        // ---------------------------
        // Send email notification
        // ---------------------------
        $invitedUser = $member->getUser();
        $senderEmail = $_ENV['MAILER_FROM'];
        $frontendUrl = $_ENV['FRONTEND_URL'];

        $html = $this->twig->render('emails/rejected.html.twig', [
            'familyName' => $family->getName(),
            'sender_email' => $senderEmail,
            'frontend_url' => $frontendUrl,
        ]);
        if ($invitedUser && $invitedUser->getEmail()) {
            $email = (new Email())
                ->from($senderEmail)
                ->to($invitedUser->getEmail())
                ->subject('Désolé, votre demande a été rejetée')
                ->html($html);

            $mailer->send($email);
        }
        return new JsonResponse(['message' => 'Family member rejected.']);
    }

}
