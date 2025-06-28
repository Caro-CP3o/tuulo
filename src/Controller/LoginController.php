<?php


namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'app_login', methods: ['POST'])]
    /**
     * Handle user login request:
     * 
     * - Validate incoming email & password.
     * - Check user existence and email verification.
     * - Validate password.
     * - Find user's family membership status.
     * - Generate JWT token and set it in httpOnly cookie.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface $jwtManager
     * @param \App\Repository\UserRepository $userRepository
     * @param \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $hasher
     * @return JsonResponse
     */
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        // --------------------------------------------------------
        // 1. Decode request and validate required fields
        // --------------------------------------------------------
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email'], $data['password'])) {
                return new JsonResponse(['error' => 'Missing credentials'], 400);
            }
            // --------------------------------------------------------
            // 2. Find user by email and check verification
            // --------------------------------------------------------
            $user = $userRepository->findOneBy(['email' => $data['email']]);

            if (!$user) {
                return new JsonResponse(['error' => 'Invalid credentials'], 401);
            }

            if (!$user->isVerified()) {
                return new JsonResponse(['error' => 'Email not verified'], 403);
            }
            // --------------------------------------------------------
            // 3. Validate password
            // --------------------------------------------------------
            if (!$hasher->isPasswordValid($user, $data['password'])) {
                return new JsonResponse(['error' => 'Invalid credentials'], 401);
            }
            // --------------------------------------------------------
            // 4. Find user's family membership status (if any)
            // --------------------------------------------------------
            $familyStatus = null;
            foreach ($user->getFamilyMembers() as $fm) {
                // Assuming only one family membership at a time
                $familyStatus = $fm->getStatus(); // active / pending / rejected
                break;
            }

            // --------------------------------------------------------
            // 5. Generate JWT token and attach as httpOnly cookie
            // --------------------------------------------------------
            $jwt = $jwtManager->create($user);

            // Set it in an httpOnly cookie
            $cookie = new Cookie(
                name: 'token',
                value: $jwt,
                expire: time() + 86400, // 1 day
                path: '/',
                domain: null,
                secure: false, // Set to true in production with HTTPS
                httpOnly: true,
                sameSite: Cookie::SAMESITE_LAX
            );

            $response = new JsonResponse([
                'message' => 'Login successful',
                'hasFamily' => $familyStatus !== null,
                'familyStatus' => $familyStatus, // important for frontend routing
            ]);
            $response->headers->setCookie($cookie);

            return $response;

        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}