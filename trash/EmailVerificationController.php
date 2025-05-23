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

        // $frontendBaseUrl = 'http://localhost:3000/verify-email';
        $frontendBaseUrl = $_ENV['FRONTEND_URL'] ?? 'http://localhost:3000/verify-email';


        if (!$code) {
            // $this->addFlash('error', 'Missing verification code.');
            // return $this->redirectToRoute('app_login');
            return $this->redirect($frontendBaseUrl . '?status=error&reason=missing_code');
        }

        $user = $userRepository->findOneBy(['emailVerificationCode' => $code]);

        // if (!$user) {
        //     $this->addFlash('error', 'Invalid or expired code.');
        //     return $this->redirectToRoute('app_login');
        // }

        // if ($user->isVerified()) {
        //     $this->addFlash('info', 'Your email is already verified.');
        //     return $this->redirectToRoute('app_login');
        // }
        if (!$user) {
            return $this->redirect($frontendBaseUrl . '?status=error&reason=invalid_code');
        }

        if ($user->isVerified()) {
            return $this->redirect($frontendBaseUrl . '?status=info&reason=already_verified');
        }

        $user->setIsVerified(true);
        $user->setEmailVerificationCode(null);
        // $user->setRegistrationStep('Step 1');

        $action = $request->query->get('action');

        // Check registration step to determine where to redirect
        // $registrationStep = $user->getRegistrationStep();

        // if ($registrationStep === 'CREATING_FAMILY') {
        //     $redirectUrl = $this->generateUrl('app_frontend_create_family'); // e.g., /create-family
        // } elseif ($registrationStep === 'JOINING_FAMILY') {
        //     $redirectUrl = $this->generateUrl('app_login'); // or wherever you want
        // } else {
        //     $redirectUrl = $this->generateUrl('app_login');
        // }

        // Optionally set registrationStep to 'DONE' if you want
        // $user->setRegistrationStep('DONE');
        if ($action === 'join') {
            $user->setRegistrationStep('JOINING_FAMILY');
        } elseif ($action === 'create') {
            $user->setRegistrationStep('CREATING_FAMILY');
        } else {
            $user->setRegistrationStep('EMAIL_VERIFIED');
        }
        // $step = $user->getRegistrationStep();

        $em->persist($user);
        $em->flush();

        // $this->addFlash('success', 'Your email has been verified. You can now log in!');

        // return $this->redirectToRoute('app_login');
        // return new RedirectResponse($redirectUrl);
        // return $this->redirect($frontendBaseUrl . '?status=success');
        return $this->redirect($frontendBaseUrl . '?status=success&step=' . $action);
    }
}
