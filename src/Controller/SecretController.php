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

#[Route('/v1')]
class SecretController extends AbstractController
{
    public function __construct(
        private SecretStorage $secretStorage,
        private SerializerInterface $serializer
    ) {}

    #[Route('/secret', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $secretValue = $request->request->get('secret');
        if (empty($secretValue)) {
            return new Response('Invalid input', Response::HTTP_BAD_REQUEST);
        }

        $secret = new Secret();
        $secret->setSecret($secretValue);
        $secret->setExpireAfterViews($request->request->getInt('expireAfterViews', 1));
        $secret->setExpireAfter($request->request->getInt('expireAfter', 0));

        $this->secretStorage->save($secret);

        return $this->createResponse($secret, $request);
    }

    #[Route('/secret/{hash}', methods: ['GET'])]
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
        $format = $request->getAcceptableContentTypes()[0] ?? 'application/json';
        $format = str_contains($format, 'xml') ? 'xml' : 'json';
        $contentType = $format === 'xml' ? 'application/xml' : 'application/json';

        return new Response(
            $this->serializer->serialize($secret, $format),
            Response::HTTP_OK,
            ['Content-Type' => $contentType]
        );
    }
}
