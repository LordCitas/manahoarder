<?php

namespace App\Controller;

use App\Entity\Decklist;
use App\Repository\DecklistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class DecksController extends AbstractController
{
    #[Route('/decks', name: 'app_decks')]
    public function index(DecklistRepository $decklistRepository): Response
    {
        // Get the current logged-in user
        $user = $this->getUser();
        
        // Fetch user's decks from database
        $decks = $decklistRepository->findBy(['user' => $user], ['createdAt' => 'DESC']);
        
        return $this->render('deck/index.html.twig', [
            'decks' => $decks,
            'user' => $user,
        ]);
    }
}
