<?php

namespace App\Registration\Infrastructure\Http\Public;

use App\Registration\Application\Command\RegisterCommand;
use App\Registration\Application\Command\RegisterHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RegistrationApiController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, RegisterHandler $handler, ValidatorInterface $validator): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $violations = $validator->validate($payload, new Assert\Collection([
            'tournamentId' => [new Assert\NotBlank(), new Assert\Uuid()],
            'lastName'     => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            'firstName'    => [new Assert\NotBlank(), new Assert\Length(max: 100)],
            'phone'        => [new Assert\NotBlank()],
            'email'        => [new Assert\Optional([new Assert\Email()])],
        ]));
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[trim($violation->getPropertyPath(), '[]')] = $violation->getMessage();
            }

            return new JsonResponse(['ok' => false, 'errors' => $errors], 422);
        }
        try {
            $result = $handler(new RegisterCommand($payload['tournamentId'], $payload['lastName'],
                $payload['firstName'], $payload['phone'], $payload['email'] ?? null));
        } catch (\DomainException $e) {
            return new JsonResponse(['ok' => false, 'message' => $e->getMessage()], 400);
        }
        $msg = match ($result->status) {
            'PENDING'      => 'Inscription enregistrée. Vous recevrez une confirmation.',
            'WAITING_LIST' => 'Le tournoi est complet — vous êtes sur liste d\'attente.',
            default        => 'Inscription enregistrée.',
        };

        return new JsonResponse(['ok' => true, 'status' => $result->status, 'message' => $msg]);
    }
}
