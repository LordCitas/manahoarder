<?php

namespace App\Controller;

use App\Service\ScryfallService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(ScryfallService $scryfallService): Response
    {
        // Buscamos una carta usando nuestro servicio
        $cardData = $scryfallService->searchCardByName('Black Lotus');

        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
            'card' => $cardData // Enviamos la información a Twig
        ]);
    }
}