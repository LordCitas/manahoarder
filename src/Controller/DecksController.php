<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DecksController extends AbstractController
{
    #[Route('/decks', name: 'app_decks')]
    public function index(): Response
    {
        return $this->render('decks/index.html.twig');
    }
}
