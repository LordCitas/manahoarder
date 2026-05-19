<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DecksController extends AbstractController
{
    #[Route('/decks', name: 'app_decks')]
    public function index(): Response
    {
        // Get the current logged-in user
        $user = $this->getUser();
        
        // TODO: Fetch user's decks from database
        // Example: $decks = $deckRepository->findBy(['user' => $user]);
        // For now, passing empty array to show empty state
        $decks = [];
        
        return $this->render('deck/index.html.twig', [
            'decks' => $decks,
            'user' => $user,
        ]);
    }
}
