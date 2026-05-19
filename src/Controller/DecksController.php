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
        // TODO: Fetch user's decks from database
        // For now, passing empty array to show empty state
        $decks = [];
        
        return $this->render('deck/index.html.twig', [
            'decks' => $decks,
        ]);
    }
}
