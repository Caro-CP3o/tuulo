<?php

namespace App\Controller;

use App\Entity\FamilyInvitation;
use App\Repository\FamilyRepository;
use App\Service\FamilyInvitationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CreateFamilyInvitationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SerializerInterface $serializer,
        private FamilyInvitationMailer $mailer,
        private FamilyRepository $familyRepository,
        private UrlGeneratorInterface $urlGenerator, // for generating the link
    ) {
    }

    #[Route('/api/family-invitations', name: 'app_create_family_invitation', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_FAMILY_ADMIN', $user->getRoles(), true)) {
            throw new AccessDeniedException('Only family admins can create invitations.');
        }

        $data = json_decode($request->getContent(), true);

        $familyIri = $data['family'] ?? null;
        $email = $data['email'] ?? null;
        $sendEmail = $data['sendEmail'] ?? false;

        if (!$familyIri) {
            throw new BadRequestHttpException('Family is required.');
        }

        $familyId = basename($familyIri);
        $family = $this->familyRepository->find($familyId);

        if (!$family) {
            throw new BadRequestHttpException('Family not found.');
        }

        // Optional: check user is a member of the family
        $isMember = false;
        foreach ($user->getFamilyMembers() as $familyMember) {
            if ($familyMember->getFamily() === $family) {
                $isMember = true;
                break;
            }
        }

        if (!$isMember) {
            throw new AccessDeniedException('You are not a member of this family.');
        }

        $invitation = new FamilyInvitation();
        $invitation->setFamily($family);

        $this->em->persist($invitation);
        $this->em->flush();

        // Generate the link to send in email
        // $inviteUrl = $this->urlGenerator->generate(
        //     'app_front_invite',
        //     ['code' => $invitation->getCode()],
        //     UrlGeneratorInterface::ABSOLUTE_URL
        // );
        $inviteUrl = 'http://localhost:3000/invite?code=' . urlencode($invitation->getCode());


        if ($sendEmail && $email) {
            $this->mailer->sendInvitation($email, $invitation, $inviteUrl);
        }

        // Always return code for frontend use
        return new JsonResponse([
            'code' => $invitation->getCode(),
            'inviteUrl' => $inviteUrl,
        ], 201);
    }
}



// namespace App\Controller;

// use App\Entity\FamilyInvitation;
// use App\Repository\FamilyRepository;
// use App\Service\FamilyInvitationMailer;
// use Doctrine\ORM\EntityManagerInterface;
// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\HttpFoundation\JsonResponse;
// use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\Routing\Annotation\Route;
// use Symfony\Component\Serializer\SerializerInterface;
// use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
// use Symfony\Component\Security\Core\Exception\AccessDeniedException;

// class CreateFamilyInvitationController extends AbstractController
// {
//     public function __construct(
//         private EntityManagerInterface $em,
//         private SerializerInterface $serializer,
//         private FamilyInvitationMailer $mailer,
//         private FamilyRepository $familyRepository
//     ) {
//     }

//     #[Route('/api/family-invitations', name: 'app_create_family_invitation', methods: ['POST'])]
//     public function __invoke(Request $request): JsonResponse
//     {
//         $user = $this->getUser();

//         if (!$user || !in_array('ROLE_FAMILY_ADMIN', $user->getRoles(), true)) {
//             throw new AccessDeniedException('Only family admins can create invitations.');
//         }

//         $data = json_decode($request->getContent(), true);

//         $familyIri = $data['family'] ?? null;
//         $email = $data['email'] ?? null;
//         $sendEmail = $data['sendEmail'] ?? false;

//         if (!$familyIri) {
//             throw new BadRequestHttpException('Family is required.');
//         }

//         $familyId = basename($familyIri);
//         $family = $this->familyRepository->find($familyId);

//         if (!$family) {
//             throw new BadRequestHttpException('Family not found.');
//         }

//         $isMember = false;
//         foreach ($user->getFamilyMembers() as $familyMember) {
//             if ($familyMember->getFamily() === $family) {
//                 $isMember = true;
//                 break;
//             }
//         }

//         if (!$isMember) {
//             throw new AccessDeniedException('You are not a member of this family.');
//         }

//         $invitation = new FamilyInvitation();
//         $invitation->setFamily($family);

//         $this->em->persist($invitation);
//         $this->em->flush();

//         if ($sendEmail && $email) {
//             $this->mailer->sendInvitation($email, $invitation);
//         }

//         $json = $this->serializer->serialize($invitation, 'json', ['groups' => ['invitation:read']]);

//         return new JsonResponse(json_decode($json), 201);
//     }
// }
