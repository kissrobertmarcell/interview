<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Secret;
use App\Service\SecretStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/secret')]
final class SecretController extends AbstractController
{
    public function __construct(
        private SecretStorage $secretStorage,
        private SerializerInterface $serializer
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): Response
    {
        if (empty($request->request->get('secret'))) {
            return new Response('Invalid input', Response::HTTP_METHOD_NOT_ALLOWED);
        }

        $secret = new Secret();
        $secret->setSecret($request->request->get('secret'));
        $secret->setExpireAfterViews($request->request->getInt('expireAfterViews', 1));
        $secret->setExpireAfter($request->request->getInt('expireAfter', 0));

        $this->secretStorage->save($secret);

        return $this->createResponse($secret, $request);
    }

    #[Route('/{hash}', methods: ['GET'])]
    public function view(string $hash, Request $request): Response
    {
        $secret = $this->secretStorage->find($hash);
        
        if (!$secret || $secret->isExpired()) {
            throw new NotFoundHttpException('Secret not found');
        }

        $secret->decrementRemainingViews();
        $this->secretStorage->save($secret);

        return $this->createResponse($secret, $request);
    }

    private function createResponse(Secret $secret, Request $request): Response
    {
        $acceptHeader = $request->getAcceptableContentTypes()[0] ?? 'application/json';
        
        if (str_contains($acceptHeader, 'xml')) {
            return new Response(
                $this->serializer->serialize($secret, 'xml'),
                Response::HTTP_OK,
                ['Content-Type' => 'application/xml']
            );
        }

        return new Response(
            $this->serializer->serialize($secret, 'json'),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}