<?php
namespace App\Controller;

use App\Entity\Secret;
use App\Service\SecretStorage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

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
        try {
            if (!$request->request->has('secret') || 
                !$request->request->has('expireAfterViews') || 
                !$request->request->has('expireAfter')) {
                throw new MethodNotAllowedHttpException([], 'Missing required parameters');
            }

            $secret = new Secret();
            $secret->setSecret($request->request->get('secret'))
                ->setExpireAfterViews((int) $request->request->get('expireAfterViews'))
                ->setExpireAfter((int) $request->request->get('expireAfter'));

            $this->secretStorage->save($secret);

            return $this->createResponse($secret, $request, Response::HTTP_OK);
        } catch (\InvalidArgumentException $e) {
            return new Response(
                json_encode(['error' => $e->getMessage()]),
                Response::HTTP_METHOD_NOT_ALLOWED,
                ['Content-Type' => 'application/json']
            );
        }
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

        return $this->createResponse($secret, $request, Response::HTTP_OK);
    }

    private function createResponse(Secret $secret, Request $request, int $status): Response
    {
        $format = $this->getResponseFormat($request);
        $contentType = $format === 'xml' ? 'application/xml' : 'application/json';
        
        $context = [
            'xml_root_node_name' => 'Secret',
            'datetime_format' => 'Y-m-d\TH:i:s.u\Z'
        ];
        
        $data = $this->serializer->serialize($secret, $format, $context);

        return new Response($data, $status, [
            'Content-Type' => $contentType
        ]);
    }

    private function getResponseFormat(Request $request): string
    {
        $accept = $request->headers->get('Accept');
        
        return match (true) {
            str_contains($accept ?? '', 'application/xml') => 'xml',
            default => 'json'
        };
    }
}