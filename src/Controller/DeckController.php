<?php

namespace App\Controller;

use App\Entity\Decklist;
use App\Entity\DeckCard;
use App\Entity\ScryfallCard;
use App\Service\ScryfallService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;

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

    #[Route('/deck/save', name: 'app_deck_save', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function saveDeck(Request $request, EntityManagerInterface $em, HttpClientInterface $httpClient): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        $deckName = $data['name'] ?? '';
        $format = $data['format'] ?? '';
        $mainDeckCards = $data['mainDeck'] ?? [];
        $sideboardCards = $data['sideboard'] ?? [];

        // Validate inputs
        if (!$deckName || !$format) {
            return new JsonResponse(['error' => 'Deck name and format are required'], 400);
        }

        if (empty($mainDeckCards)) {
            return new JsonResponse(['error' => 'Main deck cannot be empty'], 400);
        }

        // Validate legality of all cards
        try {
            $allCardIds = array_merge(array_keys($mainDeckCards), array_keys($sideboardCards));
            
            $illegalCards = [];
            foreach ($allCardIds as $cardId) {
                // Fetch card data from Scryfall API to validate legality
                try {
                    $response = $httpClient->request('GET', "https://api.scryfall.com/cards/{$cardId}");
                    $cardData = $response->toArray();
                } catch (\Exception $e) {
                    return new JsonResponse(['error' => 'Failed to validate card legality for card ID: ' . $cardId], 500);
                }

                // Check legality
                $legalities = $cardData['legalities'] ?? [];
                if (!isset($legalities[$format]) || $legalities[$format] !== 'legal') {
                    $illegalCards[] = $cardData['name'] ?? 'Unknown card';
                }
            }

            // If there are illegal cards, return error
            if (!empty($illegalCards)) {
                return new JsonResponse([
                    'error' => 'The following cards are not legal in ' . $format . ': ' . implode(', ', $illegalCards)
                ], 400);
            }

            // Create the Decklist
            $decklist = new Decklist();
            $decklist->setName($deckName);
            $decklist->setFormat($format);
            $decklist->setUser($this->getUser());
            $decklist->setCreatedAt(new \DateTimeImmutable());

            $em->persist($decklist);

            // Add main deck cards
            foreach ($mainDeckCards as $cardId => $quantity) {
                // Find or create ScryfallCard
                $scryfallCard = $em->getRepository(ScryfallCard::class)->findOneBy(['scryfallId' => $cardId]);
                
                if (!$scryfallCard) {
                    try {
                        $response = $httpClient->request('GET', "https://api.scryfall.com/cards/{$cardId}");
                        $cardData = $response->toArray();
                    } catch (\Exception $e) {
                        return new JsonResponse(['error' => 'Failed to fetch card data for ID: ' . $cardId], 500);
                    }
                    
                    $scryfallCard = new ScryfallCard();
                    $scryfallCard->setScryfallId($cardId);
                    $scryfallCard->setName($cardData['name']);
                    $scryfallCard->setManaCost($cardData['mana_cost'] ?? '');
                    $scryfallCard->setImageUrl($cardData['image_uris']['normal'] ?? $cardData['card_faces'][0]['image_uris']['normal'] ?? '');
                    $scryfallCard->setType($cardData['type_line'] ?? '');
                    $scryfallCard->setCardText($cardData['oracle_text'] ?? '');
                    $scryfallCard->setCardSet($cardData['set_name'] ?? '');
                    $scryfallCard->setCreatedAt(new \DateTimeImmutable());
                    
                    $em->persist($scryfallCard);
                }

                $deckCard = new DeckCard();
                $deckCard->setDecklist($decklist);
                $deckCard->setScryfallCard($scryfallCard);
                $deckCard->setQuantity($quantity);
                $deckCard->setIsSideboard(false);
                
                $em->persist($deckCard);
            }

            // Add sideboard cards
            foreach ($sideboardCards as $cardId => $quantity) {
                // Find or create ScryfallCard
                $scryfallCard = $em->getRepository(ScryfallCard::class)->findOneBy(['scryfallId' => $cardId]);
                
                if (!$scryfallCard) {
                    try {
                        $response = $httpClient->request('GET', "https://api.scryfall.com/cards/{$cardId}");
                        $cardData = $response->toArray();
                    } catch (\Exception $e) {
                        return new JsonResponse(['error' => 'Failed to fetch card data for ID: ' . $cardId], 500);
                    }
                    
                    $scryfallCard = new ScryfallCard();
                    $scryfallCard->setScryfallId($cardId);
                    $scryfallCard->setName($cardData['name']);
                    $scryfallCard->setManaCost($cardData['mana_cost'] ?? '');
                    $scryfallCard->setImageUrl($cardData['image_uris']['normal'] ?? $cardData['card_faces'][0]['image_uris']['normal'] ?? '');
                    $scryfallCard->setType($cardData['type_line'] ?? '');
                    $scryfallCard->setCardText($cardData['oracle_text'] ?? '');
                    $scryfallCard->setCardSet($cardData['set_name'] ?? '');
                    $scryfallCard->setCreatedAt(new \DateTimeImmutable());
                    
                    $em->persist($scryfallCard);
                }

                $deckCard = new DeckCard();
                $deckCard->setDecklist($decklist);
                $deckCard->setScryfallCard($scryfallCard);
                $deckCard->setQuantity($quantity);
                $deckCard->setIsSideboard(true);
                
                $em->persist($deckCard);
            }

            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Deck saved successfully',
                'deckId' => $decklist->getId()
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Error saving deck: ' . $e->getMessage()], 500);
        }
    }
}
