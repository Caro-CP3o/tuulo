<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\EmailVerificationMailer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;

// not in use !
final class ResendEmailVerificationController extends AbstractController
{
    #[Route('/api/resend-verification-email', name: 'resend_verification_email', methods: ['POST'])]

    /**
     * Summary of __invoke
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \App\Repository\UserRepository $userRepo
     * @param \App\Service\EmailVerificationMailer $mailer
     * @return JsonResponse
     */
    public function __invoke(Request $request, UserRepository $userRepo, EmailVerificationMailer $mailer): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        if (!$email) {
            return new JsonResponse(['error' => 'Email is required.'], 400);
        }

        $user = $userRepo->findOneBy(['email' => $email]);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found.'], 404);
        }

        if ($user->isVerified()) {
            return new JsonResponse(['message' => 'User is already verified.'], 200);
        }

        $mailer->sendVerificationCode($user, 'resend');

        return new JsonResponse(['message' => 'Verification email resent.']);
    }
}