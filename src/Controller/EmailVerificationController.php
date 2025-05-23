<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EmailVerificationController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {
    }

    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            return new Response('Missing verification code.', Response::HTTP_BAD_REQUEST);
        }
        // Find the user by the verification code
        $user = $this->userRepository->findOneBy(['emailVerificationCode' => $code]);

        if (!$user) {
            return new Response('Invalid or expired verification token.', Response::HTTP_BAD_REQUEST);
        }

        // Mark user as verified and clear the token
        $user->setIsVerified(true);
        $user->setEmailVerificationCode(null);

        // Persist changes
        $this->em->flush();

        // return new Response('Your email has been verified successfully.');
        $frontendBaseUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
        $redirectUrl = $frontendBaseUrl . '/verified-email';

        return new RedirectResponse($redirectUrl);
    }
}
