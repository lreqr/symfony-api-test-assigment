<?php

namespace App\Controller;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Psr\Log\LoggerInterface;

final class AuthController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (null === $data || empty($data['email']) || empty($data['password'])) {
                return new JsonResponse(['error' => 'Invalid input data'], Response::HTTP_BAD_REQUEST);
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return new JsonResponse(['error' => 'Invalid email format'], Response::HTTP_BAD_REQUEST);
            }

            if (strlen($data['password']) < 6) {
                return new JsonResponse(['error' => 'Password must be at least 6 characters'], Response::HTTP_BAD_REQUEST);
            }

            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                return new JsonResponse(['error' => 'Email already in use'], Response::HTTP_CONFLICT);
            }

            $user = new User();
            $user->setEmail($data['email']);
            $user->setPassword($hasher->hashPassword($user, $data['password']));
            $user->setRoles(['ROLE_USER']);

            $entityManager->persist($user);
            $entityManager->flush();

            return new JsonResponse(['status' => 'User registered'], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(
                ['error' => 'An error occurred', 'details' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
