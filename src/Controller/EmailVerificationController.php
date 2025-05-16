<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
final class EmailVerificationController extends AbstractController
{
    #[Route('/verify-email', name: 'verify_email', methods: ['POST'])]
    public function __invoke(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $code = $data['code'] ?? null;

        if (!$email || !$code) {
            return new JsonResponse(['error' => 'Missing email or code'], 400);
        }

        $user = $userRepository->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['message' => 'User already verified'], 200);
        }

        if ($user->getEmailVerificationCode() !== $code) {
            return new JsonResponse(['error' => 'Invalid verification code'], 400);
        }

        $user->setIsVerified(true);
        $user->setEmailVerificationCode(null); // Clear code
        $em->flush();

        return new JsonResponse(['message' => 'Email verified successfully'], 200);
    }
}
