<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EmailVerificationController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(Request $request): Response
    {
        $code = $request->query->get('code');
        // if (!$code) {
        //     return new Response('Missing verification code.', Response::HTTP_BAD_REQUEST);
        // }
        if (!$code) {
            return $this->json(['error' => 'Missing verification code.'], Response::HTTP_BAD_REQUEST);
        }
        // Find the user by the verification code
        $user = $this->userRepository->findOneBy(['emailVerificationCode' => $code]);

        // if (!$user) {
        //     return new Response('Invalid or expired verification token.', Response::HTTP_BAD_REQUEST);
        // }
        if (!$user) {
            return $this->json(['error' => 'Invalid or expired verification token.'], Response::HTTP_BAD_REQUEST);
        }

        // Check expiry
        $expiresAt = $user->getEmailVerificationExpiresAt();
        $now = new \DateTime();

        // if ($expiresAt === null || $expiresAt < $now) {
        //     return new Response('Verification token has expired.', Response::HTTP_BAD_REQUEST);
        // }

        if ($expiresAt === null || $expiresAt <= $now) {
            return $this->json(['error' => 'Verification token has expired.'], Response::HTTP_BAD_REQUEST);
        }

        if ($user->isVerified()) {
            return $this->json(['message' => 'Email already verified.'], Response::HTTP_OK);
        }

        // Mark user as verified and clear the token
        $user->setIsVerified(true);
        $user->setEmailVerificationCode(null);
        $user->setEmailVerificationExpiresAt(null);

        // Persist changes
        $this->em->flush();

        // Log successful verification
        $this->logger->info('Email verified for user ID: ' . $user->getId());

        // return new Response('Your email has been verified successfully.');
        $frontendBaseUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000';
        $redirectUrl = $frontendBaseUrl . '/verified-email';
        // $redirectUrl = $frontendBaseUrl . '/verify-email?code=' . $code;
        // $redirectUrl = $frontendBaseUrl . '/verify-email?code=' . urlencode($code);
        // $redirectUrl = $frontendBaseUrl . '/verify-email?code=' . urlencode($code) . '&success=true';



        return new RedirectResponse($redirectUrl);

    }

}
