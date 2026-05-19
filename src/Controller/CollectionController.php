<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CollectionController extends AbstractController
{
    #[Route('/collection', name: 'app_collection')]
    public function index(): Response
    {
        // TODO: Fetch user's albums from database
        // For now, passing empty array to show empty state
        $albums = [];
        
        return $this->render('collection/index.html.twig', [
            'albums' => $albums,
        ]);
    }
}
