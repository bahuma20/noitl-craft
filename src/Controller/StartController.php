<?php

namespace App\Controller;

use App\MinecraftService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

class StartController extends AbstractController
{


    public function __construct(private MinecraftService $minecraftService)
    {
    }

    #[Route('/', name: 'start')]
    public function start()
    {
        return $this->render('start.html.twig');
    }

    #[Route('/start-server', name: 'start_server', methods: ['POST'])]
    public function startServer()
    {
        $this->minecraftService->start();
        return new RedirectResponse('/');
    }

}