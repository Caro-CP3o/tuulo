<?php

namespace App\Controller;

use App\Entity\Family;
use App\Entity\User;
use App\Entity\FamilyMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestBody;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateFamilyController extends AbstractController
{
    #[Route('/api/family/create', name: 'api_create_family', methods: ['POST'])]
    public function __invoke(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        TokenStorageInterface $tokenStorage
    ): JsonResponse {

        // /** @var User $user */
        // $user = $this->getUser();
        // if (!$user || !$user->isVerified()) {
        //     return $this->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        // }

        /** @var User $user */
        $user = $tokenStorage->getToken()?->getUser();

        if (!$user || !$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);

        $family = new Family();
        $family->setName($data['name'] ?? '');
        $family->setDescription($data['description'] ?? null);
        $family->setCoverImage($data['coverImage'] ?? null);
        // $family->setJoinCode(strtoupper(substr(Uuid::v4()->toRfc4122(), 0, 8))); // Unique join code
        $family->setCreator($user);

        $errors = $validator->validate($family);
        if (count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }


        // Create FamilyMember for the creator
        $member = new FamilyMember();
        $member->setUser($user);
        $member->setFamily($family);
        $member->setStatus('active');


        // Maintain both sides of the relation
        $family->addFamilyMember($member);
        $user->addFamilyMember($member);

        $roles = $user->getRoles();
        if (!in_array('ROLE_FAMILY_ADMIN', $roles)) {
            $roles[] = 'ROLE_FAMILY_ADMIN';
            $user->setRoles($roles);
        }


        $em->persist($family);
        $em->persist($member);
        $em->persist($user);

        $em->flush();

        return $this->json([
            'id' => $family->getId(),
            'name' => $family->getName(),
            // 'joinCode' => $family->getJoinCode(),
        ], Response::HTTP_CREATED);
    }
}
