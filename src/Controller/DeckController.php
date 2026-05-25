<?php

namespace App\Controller;

use App\Service\ScryfallService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DeckController extends AbstractController
{
    #[Route('/deck/create', name: 'app_deck_create', methods: ['GET'])]
    public function create(): Response
    {
        return $this->render('deck/create.html.twig');
    }

    #[Route('/deck/search-cards', name: 'app_deck_search_cards', methods: ['GET'])]
    public function searchCards(Request $request, ScryfallService $scryfallService): JsonResponse
    {
        $query = $request->query->get('q', '');
        $format = $request->query->get('format', '');
        $page = $request->query->getInt('page', 1);

        if (strlen($query) < 2) {
            return new JsonResponse(['error' => 'Query too short'], 400);
        }

        // Build search query with format filter for legality
        $searchQuery = $query;
        if (!empty($format)) {
            // Use legal: operator to filter only cards legal in the selected format
            $searchQuery .= ' legal:' . $format;
        }

        try {
            $results = $scryfallService->searchCards($searchQuery, $page);
            
            $cards = [];
            foreach ($results['data'] ?? [] as $cardData) {
                $imageUrl = '';
                if (isset($cardData['image_uris']['normal'])) {
                    $imageUrl = $cardData['image_uris']['normal'];
                } elseif (isset($cardData['card_faces'][0]['image_uris']['normal'])) {
                    $imageUrl = $cardData['card_faces'][0]['image_uris']['normal'];
                }

                $cards[] = [
                    'id' => $cardData['id'],
                    'name' => $cardData['name'],
                    'image' => $imageUrl,
                    'mana_cost' => $cardData['mana_cost'] ?? '',
                    'type_line' => $cardData['type_line'] ?? '',
                    'rarity' => $cardData['rarity'] ?? '',
                    'set' => $cardData['set_name'] ?? '',
                ];
            }

            return new JsonResponse([
                'cards' => $cards,
                'total' => $results['total_cards'] ?? 0,
                'has_more' => $results['has_more'] ?? false,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
