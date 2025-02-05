<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MapController extends AbstractController
{

    #[Route('/map', name: 'map_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('map.html.twig');
    }
}