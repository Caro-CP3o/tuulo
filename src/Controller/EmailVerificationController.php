<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class EmailVerificationController extends AbstractController
{

    #[Route('/verify-email', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $code = $request->query->get('code');

        if (!$code) {
            $this->addFlash('error', 'Missing verification code.');
            return $this->redirectToRoute('app_login');
        }

        $user = $userRepository->findOneBy(['emailVerificationCode' => $code]);

        if (!$user) {
            $this->addFlash('error', 'Invalid or expired code.');
            return $this->redirectToRoute('app_login');
        }

        if ($user->isVerified()) {
            $this->addFlash('info', 'Your email is already verified.');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setEmailVerificationCode(null);

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Your email has been verified. You can now log in!');

        return $this->redirectToRoute('app_login');
    }
}
