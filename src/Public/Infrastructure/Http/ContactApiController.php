<?php

namespace App\Public\Infrastructure\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ContactApiController extends AbstractController
{
    private const CONTACT_EMAIL = 'kevin.nadin@gmail.com';
//    private const CONTACT_EMAIL = 'vincent.pottelette@gmail.com';
//    private const CONTACT_EMAIL = 'astc-revigny@alwaysdata.net';

    #[Route('/api/contact', name: 'api_contact', methods: ['POST'])]
    public function contact(Request $request, MailerInterface $mailer, ValidatorInterface $validator): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];

        $violations = $validator->validate($payload, new Assert\Collection([
            'email'   => [new Assert\NotBlank(), new Assert\Email()],
            'subject' => [new Assert\NotBlank(), new Assert\Length(max: 150)],
            'message' => [new Assert\NotBlank(), new Assert\Length(max: 5000)],
        ]));

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[trim($violation->getPropertyPath(), '[]')] = $violation->getMessage();
            }

            return new JsonResponse(['ok' => false, 'errors' => $errors], 422);
        }

        $email = (new Email())
            ->from('noreply@astc-revigny.fr')
            ->to(self::CONTACT_EMAIL)
            ->replyTo($payload['email'])
            ->subject('[Contact ASTC Revigny] ' . $payload['subject'])
            ->text("Message de : {$payload['email']}\n\n{$payload['message']}");

        $mailer->send($email);

        return new JsonResponse(['ok' => true, 'message' => 'Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.']);
    }
}
