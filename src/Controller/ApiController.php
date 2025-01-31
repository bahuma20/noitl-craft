<?php

namespace App\Controller;

use App\MinecraftService;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;

class ApiController extends AbstractController
{


    public function __construct(private MinecraftService $minecraftService)
    {
    }

    #[Route('/api/status', name: 'api_status', methods: ['GET'])]
    function getStatus(CacheInterface $serverStatusCache): Response
    {

        $status = $serverStatusCache->get('default', function () {
            return $this->minecraftService->getStatus();
        });

        return new JsonResponse($status);
    }

    #[Route('/api/start-server', name: 'api_start_server', methods: ['POST'])]
    function startServer(CacheItemPoolInterface $rateLimiter): Response
    {
        $factory = new RateLimiterFactory([
            'id' => 'start_server',
            'policy' => 'sliding_window',
            'limit' => 1,
            'interval' => '60 seconds',
        ], new CacheStorage($rateLimiter));

        $limiter = $factory->create('global');
        $allowed = $limiter->consume()->isAccepted();

        if (!$allowed) {
            throw new TooManyRequestsHttpException(null, 'Der Server wurde gerade erst gestartet. Warte einige Sekunden.');
        }

        $this->minecraftService->start();

        return new JsonResponse([
            'status' => 'OK'
        ]);
    }
}