<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CollectionController extends AbstractController
{
    #[Route('/collection', name: 'app_collection')]
    public function index(): Response
    {
        // Get the current logged-in user
        $user = $this->getUser();
        
        // TODO: Fetch user's albums from database
        // Example: $albums = $albumRepository->findBy(['user' => $user]);
        // For now, passing empty array to show empty state
        $albums = [];
        
        return $this->render('collection/index.html.twig', [
            'albums' => $albums,
            'user' => $user,
        ]);
    }
}
