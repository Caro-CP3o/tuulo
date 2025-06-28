<?php

namespace App\Controller;

use App\Entity\FamilyInvitation;
use App\Repository\FamilyRepository;
use App\Repository\FamilyMemberRepository;
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
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    #[Route('/api/family-invitations', name: 'app_create_family_invitation', methods: ['POST'])]
    /**
     * Create a new family invitation.
     * Accessible only to users with ROLE_FAMILY_ADMIN.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @return JsonResponse
     */
    public function __invoke(Request $request): JsonResponse
    {
        // ---------------------------
        // Get the currently authenticated user
        // ---------------------------
        $user = $this->getUser();
        // ---------------------------
        // Check user has ROLE_FAMILY_ADMIN
        // ---------------------------
        if (!$user || !in_array('ROLE_FAMILY_ADMIN', $user->getRoles(), true)) {
            throw new AccessDeniedException('Seul un administrateur de famille peut créer une invitation.');
        }
        // ---------------------------
        // Decode JSON payload from the request
        // Expected keys: family (IRI), email (optional), sendEmail (boolean)
        // ---------------------------
        $data = json_decode($request->getContent(), true);

        $familyIri = $data['family'] ?? null;
        $email = $data['email'] ?? null;
        $sendEmail = $data['sendEmail'] ?? false;

        if (!$familyIri) {
            throw new BadRequestHttpException('Le famille IRI est requis.');
        }

        $familyId = basename($familyIri);
        $family = $this->familyRepository->find($familyId);
        // ---------------------------
        // Validate: family IRI is required
        // ---------------------------
        if (!$family) {
            throw new BadRequestHttpException('Famille introuvable.');
        }
        // ---------------------------
        // Extract the family ID from IRI and fetch family from database
        // ---------------------------
        $isMember = false;
        foreach ($user->getFamilyMembers() as $familyMember) {
            if ($familyMember->getFamily() === $family) {
                $isMember = true;
                break;
            }
        }
        // ---------------------------
        // Ensure user is actually a member of this family
        // ---------------------------
        if (!$isMember) {
            throw new AccessDeniedException('Vous n\'êtes pas membre de cette famille.');
        }
        // ---------------------------
        // Create and persist new FamilyInvitation entity
        // ---------------------------
        $invitation = new FamilyInvitation();
        $invitation->setFamily($family);

        $this->em->persist($invitation);
        $this->em->flush();

        // ---------------------------
        // Build the invitation URL for frontend
        // ---------------------------
        $frontendUrl = $_ENV['FRONTEND_URL'];
        $inviteUrl = $frontendUrl . '/invite?code=' . urlencode($invitation->getCode());

        // ---------------------------
        // Send invitation email if requested and email is provided
        // ---------------------------
        if ($sendEmail && $email) {
            $this->mailer->sendInvitation($email, $invitation, $inviteUrl);
        }
        // ---------------------------
        // Return JSON response with invitation code and URL
        // ---------------------------
        return new JsonResponse([
            'code' => $invitation->getCode(),
            'inviteUrl' => $inviteUrl,
        ], 201);
    }
}