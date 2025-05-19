<?php

// namespace App\Controller;

// use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// use Symfony\Component\HttpFoundation\Response;
// use Symfony\Component\Routing\Attribute\Route;

// final class RegisterViaInviteController extends AbstractController
// {
//     #[Route('/register/via/invite', name: 'app_register_via_invite')]
//     public function index(): Response
//     {
//         return $this->render('register_via_invite/index.html.twig', [
//             'controller_name' => 'RegisterViaInviteController',
//         ]);
//     }
// }


namespace App\Controller;

use App\Entity\User;
use App\Repository\FamilyMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterViaInviteController extends AbstractController
{
    #[Route('/api/register', name: 'register_via_invite', methods: ['POST'])]
    public function register(
        Request $request,
        FamilyMemberRepository $memberRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $token = $data['token'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$token || !$email || !$password) {
            return new JsonResponse(['error' => 'Missing data.'], 400);
        }

        $member = $memberRepo->findOneBy(['token' => $token, 'status' => 'approved']);

        if (!$member) {
            return new JsonResponse(['error' => 'Invalid or expired token.'], 404);
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $em->persist($user);

        // Link user to FamilyMember
        $member->setUser($user);
        $member->setStatus('active');

        $em->flush();

        // Optionally trigger email verification here...

        return new JsonResponse(['message' => 'Registration complete.']);
    }
}
