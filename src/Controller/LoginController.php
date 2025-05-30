<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticatorManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginController extends AbstractController
{
    #[Route('/api/login', name: 'app_login', methods: ['POST'])]
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager,
        UserRepository $userRepository,
        UserPasswordHasherInterface $hasher
    ): JsonResponse {
        // $data = json_decode($request->getContent(), true);

        // $user = $userRepository->findOneBy(['email' => $data['email']]);

        // if (!$user->isVerified()) {
        //     return new JsonResponse(['error' => 'Email not verified'], 403);
        // }

        // if (!$user || !$hasher->isPasswordValid($user, $data['password'])) {
        //     return new JsonResponse(['error' => 'Invalid credentials'], 401);
        // }

        // $jwt = $jwtManager->create($user);


        // $cookie = new Cookie(
        //     name: 'BEARER',
        //     value: $jwt,
        //     expire: time() + 3600,
        //     path: '/',
        //     domain: null,
        //     secure: false, // Set to true in production (HTTPS)
        //     httpOnly: true,
        //     raw: false,
        //     sameSite: Cookie::SAMESITE_LAX
        // );

        // $response = new JsonResponse(['message' => 'Login successful']);
        // $response->headers->setCookie($cookie);

        // return $response;
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['email']) || !isset($data['password'])) {
                return new JsonResponse(['error' => 'Missing credentials'], 400);
            }

            $user = $userRepository->findOneBy(['email' => $data['email']]);
            if (!$user) {
                return new JsonResponse(['error' => 'User not found'], 401);
            }

            if (!$user->isVerified()) {
                return new JsonResponse(['error' => 'Email not verified'], 403);
            }

            if (!$hasher->isPasswordValid($user, $data['password'])) {
                return new JsonResponse(['error' => 'Invalid credentials'], 401);
            }

            $jwt = $jwtManager->create($user);

            $cookie = new Cookie(
                name: 'token',
                value: $jwt,
                expire: 0,
                path: '/',
                domain: null,
                secure: false,
                httpOnly: true,
                sameSite: Cookie::SAMESITE_LAX
            );

            $response = new JsonResponse(['message' => 'Login successful']);
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

