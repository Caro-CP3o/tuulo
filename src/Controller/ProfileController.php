<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ProfileController extends AbstractController
{


    // #[Route('/profile', name: 'app_profile')]
    // public function index(): Response
    // {
    //     return $this->render('profile/index.html.twig', [
    //         'controller_name' => 'ProfileController',
    //     ]);
    // }
    #[Route('/api/profile', name: 'app_profile', methods: ['GET'])]
    public function profile(Security $security): JsonResponse
    {
        $user = $security->getUser();

        return $this->json($user, 200, [], ['groups' => ['user:read']]);
    }
}
