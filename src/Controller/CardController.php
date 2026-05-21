<?php

namespace App\Controller;

use App\Service\ScryfallService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class CardController extends AbstractController
{
    public function __construct(
        private ScryfallService $scryfallService
    ) {}

    #[Route('/card/autocomplete', name: 'app_card_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        
        if (strlen($query) < 2) {
            return $this->json([]);
        }

        $suggestions = $this->scryfallService->autocompleteCardNames($query);
        
        return $this->json($suggestions);
    }

    #[Route('/card/autocomplete/types', name: 'app_card_autocomplete_types', methods: ['GET'])]
    public function autocompleteTypes(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = $this->scryfallService->autocompleteTypes($query);
        return $this->json($suggestions);
    }

    #[Route('/card/autocomplete/artists', name: 'app_card_autocomplete_artists', methods: ['GET'])]
    public function autocompleteArtists(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = $this->scryfallService->autocompleteArtists($query);
        return $this->json($suggestions);
    }

    #[Route('/card/autocomplete/sets', name: 'app_card_autocomplete_sets', methods: ['GET'])]
    public function autocompleteSets(Request $request): JsonResponse
    {
        $query = $request->query->get('q', '');
        $suggestions = $this->scryfallService->autocompleteSets($query);
        return $this->json($suggestions);
    }

    #[Route('/card/search', name: 'app_card_search', methods: ['GET'])]
    public function search(Request $request): Response
    {
        $query = $request->query->get('q', '');
        $filters = [
            'types' => $request->query->all('types') ?? [],
            'rarity' => $request->query->get('rarity', ''),
            'colors' => $request->query->all('colors') ?? [],
            'colorIdentity' => $request->query->get('color_identity', ''),
            'format' => $request->query->get('format', ''),
            'cmc' => $request->query->get('cmc', ''),
            'artist' => $request->query->get('artist', ''),
            'set' => $request->query->get('set', ''),
        ];
        
        $cards = [];
        $searchQuery = $this->buildSearchQuery($query, $filters);

        if (strlen($searchQuery) >= 2) {
            $results = $this->scryfallService->searchCards($searchQuery);
            
            // Format cards for display
            foreach ($results as $cardData) {
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
                    'set' => $cardData['set_name'] ?? '',
                    'type' => $cardData['type_line'] ?? '',
                    'rarity' => $cardData['rarity'] ?? '',
                ];
            }
            
            // If exactly one result, redirect to card view
            if (count($cards) === 1) {
                return $this->redirectToRoute('app_card_view', ['id' => $cards[0]['id']]);
            }
        }

        return $this->render('card/search.html.twig', [
            'query' => $query,
            'cards' => $cards,
            'filters' => $filters,
        ]);
    }

    private function buildSearchQuery(string $name, array $filters): string
    {
        $parts = [];
        
        if (!empty($name)) {
            $parts[] = $name;
        }
        
        if (!empty($filters['types']) && is_array($filters['types'])) {
            foreach ($filters['types'] as $type) {
                $parts[] = 't:' . $type;
            }
        }
        
        if (!empty($filters['rarity'])) {
            $parts[] = 'r:' . $filters['rarity'];
        }
        
        if (!empty($filters['colors']) && is_array($filters['colors'])) {
            $colorString = implode('', $filters['colors']);
            if (!empty($colorString)) {
                $parts[] = 'c:' . $colorString;
            }
        }
        
        if (!empty($filters['colorIdentity'])) {
            $parts[] = 'id:' . $filters['colorIdentity'];
        }
        
        if (!empty($filters['format'])) {
            $parts[] = 'f:' . $filters['format'];
        }
        
        if (!empty($filters['cmc'])) {
            $parts[] = 'cmc' . $filters['cmc'];
        }
        
        if (!empty($filters['artist'])) {
            $parts[] = 'a:' . $filters['artist'];
        }
        
        if (!empty($filters['set'])) {
            $parts[] = 'e:' . $filters['set'];
        }
        
        return implode(' ', $parts);
    }

    #[Route('/card/{id}', name: 'app_card_view', methods: ['GET'])]
    public function view(string $id, ScryfallService $scryfallService): Response
    {
        try {
            // Fetch card data from Scryfall API by ID
            $cardData = $scryfallService->searchCardById($id);
            
            if (!$cardData) {
                throw $this->createNotFoundException('Card not found');
            }
            
            // Extract image URLs
            $images = [];
            if (isset($cardData['image_uris'])) {
                $images[] = [
                    'normal' => $cardData['image_uris']['normal'] ?? '',
                    'large' => $cardData['image_uris']['large'] ?? '',
                ];
            } elseif (isset($cardData['card_faces'])) {
                foreach ($cardData['card_faces'] as $face) {
                    $images[] = [
                        'normal' => $face['image_uris']['normal'] ?? '',
                        'large' => $face['image_uris']['large'] ?? '',
                        'name' => $face['name'] ?? '',
                    ];
                }
            }
            
            // Format card data
            $card = [
                'id' => $cardData['id'],
                'name' => $cardData['name'],
                'mana_cost' => $cardData['mana_cost'] ?? '',
                'cmc' => $cardData['cmc'] ?? 0,
                'type_line' => $cardData['type_line'] ?? '',
                'oracle_text' => $cardData['oracle_text'] ?? '',
                'flavor_text' => $cardData['flavor_text'] ?? '',
                'power' => $cardData['power'] ?? null,
                'toughness' => $cardData['toughness'] ?? null,
                'loyalty' => $cardData['loyalty'] ?? null,
                'colors' => $cardData['colors'] ?? [],
                'color_identity' => $cardData['color_identity'] ?? [],
                'rarity' => $cardData['rarity'] ?? '',
                'set_name' => $cardData['set_name'] ?? '',
                'set' => $cardData['set'] ?? '',
                'collector_number' => $cardData['collector_number'] ?? '',
                'artist' => $cardData['artist'] ?? '',
                'prices' => $cardData['prices'] ?? [],
                'legalities' => $cardData['legalities'] ?? [],
                'images' => $images,
                'keywords' => $cardData['keywords'] ?? [],
                'card_faces' => $cardData['card_faces'] ?? null,
                'layout' => $cardData['layout'] ?? 'normal',
            ];
            
            return $this->render('card/view.html.twig', [
                'card' => $card,
            ]);
        } catch (\Exception $e) {
            throw $this->createNotFoundException('Card not found: ' . $e->getMessage());
        }
    }
}
