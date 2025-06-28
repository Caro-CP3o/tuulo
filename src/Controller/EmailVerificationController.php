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
    /**
     * Summary of __construct
     * @param \App\Repository\UserRepository $userRepository
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET'])]
    /**
     * Verifies the user's email based on a verification code from the query string.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\JsonResponse
     */
    public function verifyEmail(Request $request): Response
    {
        // ---------------------------
        // Validate verification code from query parameter
        // ---------------------------
        $code = $request->query->get('code');

        if (!$code) {
            return $this->json(['error' => 'Missing verification code.'], Response::HTTP_BAD_REQUEST);
        }
        // ---------------------------
        // Find user by verification code
        // ---------------------------
        $user = $this->userRepository->findOneBy(['emailVerificationCode' => $code]);

        if (!$user) {
            return $this->json(['error' => 'Invalid or expired verification token.'], Response::HTTP_BAD_REQUEST);
        }

        // ---------------------------
        // Check if the token has expired
        // ---------------------------
        $expiresAt = $user->getEmailVerificationExpiresAt();
        $now = new \DateTime();

        if ($expiresAt === null || $expiresAt <= $now) {
            return $this->json(['error' => 'Verification token has expired.'], Response::HTTP_BAD_REQUEST);
        }
        // ---------------------------
        // Handle already verified users
        // ---------------------------
        if ($user->isVerified()) {
            return $this->json(['message' => 'Email already verified.'], Response::HTTP_OK);
        }

        // ---------------------------
        // Mark user as verified and clear verification fields
        // ---------------------------
        $user->setIsVerified(true);
        $user->setEmailVerificationCode(null);
        $user->setEmailVerificationExpiresAt(null);

        // Persist changes to database
        $this->em->flush();

        // ---------------------------
        // Log the successful verification
        // ---------------------------
        $this->logger->info('Email verified for user ID: ' . $user->getId());

        // ---------------------------
        // Redirect to frontend confirmation page
        // ---------------------------
        $frontendBaseUrl = $_ENV['FRONTEND_URL'] ?? 'https://www.tuulo.be/verified-email';
        $redirectUrl = $frontendBaseUrl . '/verified-email';

        return new RedirectResponse($redirectUrl);

    }

}
