<?php

// src/Controller/LogoutController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class LogoutController extends AbstractController
{
    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function logout(): Response
    {
        $cookie = Cookie::create(
            name: 'token',
            value: '',
            expire: time() - 3600, // Expired in the past
            path: '/',
            domain: null,
            secure: false, // true in prod
            httpOnly: true,
            sameSite: Cookie::SAMESITE_LAX
        );

        $response = new JsonResponse(['message' => 'Logged out']);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
