<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\FamilyMemberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\EmailVerificationMailer;
use Symfony\Component\Routing\Attribute\Route;

final class RegisterViaInviteController extends AbstractController
{

    #[Route('/api/register', name: 'register_via_invite', methods: ['POST'])]
    public function register(
        Request $request,
        FamilyMemberRepository $memberRepo,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        EmailVerificationMailer $mailer
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

        if ($member->getUser()) {
            return new JsonResponse(['error' => 'Token already used.'], 409);
        }

        // Create user
        $user = new User();
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        if (!$user->isVerified()) {
            $code = random_int(100000, 999999);
            $user->setEmailVerificationCode((string) $code);
            $user->setIsVerified(false);

            // Send the email
            $mailer->sendVerificationCode($user);
        }

        $em->persist($user);

        // Link user to FamilyMember
        $member->setUser($user);
        $member->setStatus('active');

        $em->flush();

        // message be go check your emails ?
        return new JsonResponse(['message' => 'Registration complete.']);
    }
}
