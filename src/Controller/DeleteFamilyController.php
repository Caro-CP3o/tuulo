<?php



namespace App\Controller;

use App\Entity\User;
use App\Repository\FamilyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

#[IsGranted('ROLE_FAMILY_ADMIN')]
final class DeleteFamilyController extends AbstractController
{
    /**
     * Summary of __construct
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     * @param \Twig\Environment $twig
     */
    public function __construct(
        private MailerInterface $mailer,
        private Environment $twig
    ) {
    }

    #[Route('/api/family/{id}', name: 'api_delete_family', methods: ['DELETE'])]
    /**
     * Deletes a family if the current user is its creator, and notifies all family members by email.
     *
     * @param int $id
     * @param \App\Repository\FamilyRepository $familyRepository
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @param \Symfony\Component\Mailer\MailerInterface $mailer
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
     * @return JsonResponse
     */
    public function __invoke(
        int $id,
        FamilyRepository $familyRepository,
        EntityManagerInterface $em,
        Security $security,
        MailerInterface $mailer,
        TokenStorageInterface $tokenStorage,
        Environment $twig,
    ): JsonResponse {
        // ---------------------------
        // Validate user and check permissions
        // ---------------------------
        /** @var User|null $user */
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        // Find the family to delete
        $family = $familyRepository->find($id);
        if (!$family) {
            return $this->json(['error' => 'Family not found'], Response::HTTP_NOT_FOUND);
        }
        // Ensure the current user is the creator of the family
        if ($family->getCreator() !== $user) {
            return $this->json(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }
        // ---------------------------
        // Notify each family member and remove their membership
        // ---------------------------
        foreach ($family->getFamilyMembers() as $member) {
            $memberUser = $member->getUser();

            $senderEmail = $_ENV['MAILER_FROM'];
            $frontendUrl = $_ENV['FRONTEND_URL'];

            // Render email content using Twig
            $html = $this->twig->render('emails/familyDeleted.html.twig', [
                'familyName' => $family->getName(),
                'sender_email' => $senderEmail,
                'frontend_url' => $frontendUrl,
                'member_name' => $memberUser->getFirstName(),
            ]);

            try {
                // Create and send the email               
                $email = (new Email())
                    ->from($senderEmail)
                    ->to($memberUser->getEmail())
                    ->subject('Famille supprimée')
                    ->html($html);
                $mailer->send($email);
            } catch (\Throwable $e) {
                // Swallow exceptions to ensure deletion proceeds even if email fails              
            }

            $em->remove($member);
        }


        $em->remove($family);

        $em->flush();
        // Clear the user's authentication token
        $tokenStorage->setToken(null);

        return $this->json(['message' => 'Family deleted'], Response::HTTP_NO_CONTENT);
    }
}