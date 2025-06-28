<?php

namespace App\Controller;

use App\Entity\Family;
use App\Entity\User;
use App\Entity\FamilyMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateFamilyController extends AbstractController
{
    #[Route('/api/family/create', name: 'api_create_family', methods: ['POST'])]
    /**
     * Handle creation of a new family by a verified user.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
     * @return JsonResponse
     */
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {
        // ---------------------------
        // Retrieve the currently authenticated user
        // ---------------------------
        /** @var User $user */
        $user = $tokenStorage->getToken()?->getUser();

        // ---------------------------
        // Ensure the user exists and has verified their email
        // ---------------------------        
        if (!$user || !$user->isVerified()) {
            return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }
        // ---------------------------
        // Check if request is multipart (coverImage upload)
        // ---------------------------
        $isMultipart = str_contains($request->headers->get('Content-Type'), 'multipart/form-data');
        // ---------------------------
        // Extract family name and description from the request
        // ---------------------------
        $name = $request->get('name');
        $description = $request->get('description');

        // ---------------------------
        // Remove any existing family memberships from the user
        // ---------------------------
        foreach ($user->getFamilyMembers()->toArray() as $oldMembership) {
            $oldMembership->getFamily()?->removeFamilyMember($oldMembership);
            $user->removeFamilyMember($oldMembership);
        }
        $em->flush();

        // ---------------------------
        // Create new Family entity and set basic info
        // ---------------------------
        $family = new Family();
        $family->setName($name);
        $family->setDescription($description);
        $family->setCreator($user);

        // ---------------------------
        // Handle cover image upload if present
        // ---------------------------
        $coverImageFile = $request->files->get('coverImage');
        if ($coverImageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            $mediaObject = new \App\Entity\MediaObject();
            $mediaObject->setFile($coverImageFile);
            $mediaObject->setUser($user);

            $em->persist($mediaObject);
            $family->setCoverImage($mediaObject);
        }
        // ---------------------------
        // Validate the family entity
        // ---------------------------
        $errors = $validator->validate($family);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        // ---------------------------
        // Create FamilyMember link between user and new family
        // ---------------------------
        $member = new FamilyMember();
        $member->setUser($user);
        $member->setFamily($family);
        $member->setStatus('active');
        // ---------------------------
        // Establish bi-directional relationships
        // ---------------------------
        $family->addFamilyMember($member);
        $user->addFamilyMember($member);
        // ---------------------------
        // Grant user admin role within the family
        // ---------------------------
        $user->setRoles(array_values(array_unique(array_merge($user->getRoles(), ['ROLE_USER', 'ROLE_FAMILY_ADMIN']))));

        // ---------------------------
        // Persist all entities and flush to database
        // ---------------------------
        $em->persist($family);
        $em->persist($member);
        $em->persist($user);

        $em->flush();
        // ---------------------------
        // Return JSON response with newly created family info
        // ---------------------------
        return $this->json([
            'id' => $family->getId(),
            'name' => $family->getName(),
        ], Response::HTTP_CREATED);
    }

}