<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;
// use Symfony\Component\RateLimiter\Policy\Limit;

#[Route('/api', name: 'api_')]
final class UserRegistrationController extends AbstractController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em, RateLimiterFactory $anonymousApiLimiter): JsonResponse
    {
        // Apply rate limiting (before anything else)
        $limiter = $anonymousApiLimiter->create($request->getClientIp());
        $limit = $limiter->consume();

        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter()->getTimestamp() - (new \DateTime())->getTimestamp();

            return $this->json([
                'error' => 'Trop de tentatives. Merci de réessayer dans ' . $retryAfter . ' secondes.'
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => $retryAfter
            ]);
        }

        $data = json_decode($request->getContent(), true);

        // Validate required fields
        $requiredFields = ['email', 'firstname', 'lastname', 'birthDate', 'color', 'password'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return $this->json([
                    'error' => sprintf('The field "%s" is required.', $field)
                ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
            }
        }
        // Validate email format
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => 'L\'adresse email fournie n\'est pas valide.'
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }
        // Check if email already exists (IMPORTANT)
        $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        if ($existingUser) {
            return $this->json([
                'error' => 'Cet email est déjà utilisé. Veuillez en choisir un autre.'
            ], JsonResponse::HTTP_CONFLICT); // 409 Conflict
        }
        // Validate birth date
        if (new \DateTime($data['birthDate']) > new \DateTime()) {
            return $this->json([
                'error' => 'La date de naissance ne peut pas être dans le futur.'
            ], JsonResponse::HTTP_BAD_REQUEST); // 400 Bad Request
        }

        // Create a new user
        $user = new User();
        $user->setEmail($data['email']);
        $user->setFirstname($data['firstname']);
        $user->setLastname($data['lastname']);
        $user->setBirthDate(new \DateTime($data['birthDate']));
        $user->setColor($data['color']);

        // Hash the password securely
        // $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        // $user->setPassword($hashedPassword);

        // Hash the password securely before saving
        $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
        var_dump($hashedPassword);
        $user->setPassword($hashedPassword); // Ensure hashed password is set

        // Save the user to the database
        try {
            $em->persist($user);
            $em->flush();
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Une erreur est survenue lors de la création de l\'utilisateur.',
                'details' => $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR); // 500 Internal Server Error
        }

        return $this->json(['message' => 'l\'utilisateur a été créé avec succès'], JsonResponse::HTTP_CREATED); // 201 Created
    }
}
