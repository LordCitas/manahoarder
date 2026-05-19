<?php

namespace App\Service;

use App\Entity\ScryfallCard;
use App\Repository\ScryfallCardRepository;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\ORM\EntityManagerInterface;

class ScryfallService
{
    private const SCRYFALL_API = 'https://api.scryfall.com';
    private const USER_AGENT = 'ManaHoarderApp/1.0';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        private ScryfallCardRepository $scryfallCardRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Busca una carta por nombre con estrategia de caché perezosa.
     * 
     * Flujo:
     * 1. Si está en la BD local, la devuelve instantáneamente
     * 2. Si no está, busca en Scryfall y la cachea
     * 3. Respeta el rate limit de Scryfall
     */
    public function searchCardByName(string $cardName): ?ScryfallCard
    {
        // Paso 1: ¿Está en la caché local?
        $cachedCard = $this->scryfallCardRepository->findOneBy(['name' => $cardName]);
        
        if ($cachedCard) {
            return $cachedCard;
        }

        // Paso 2: No está cacheada, buscar en Scryfall con búsqueda exacta
        try {
            $response = $this->httpClient->request('GET', self::SCRYFALL_API . '/cards/named', [
                'query' => ['exact' => $cardName],
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $cardData = $response->toArray();

            // Paso 3: Cachear en la BD local
            return $this->cacheCard($cardData);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Búsqueda fuzzy (aproximada) con caché
     */
    public function fuzzySearchCard(string $cardName): ?ScryfallCard
    {
        // Primero intentar búsqueda exacta en caché
        $cachedCard = $this->scryfallCardRepository->findOneBy(['name' => $cardName]);
        if ($cachedCard) {
            return $cachedCard;
        }

        try {
            $response = $this->httpClient->request('GET', self::SCRYFALL_API . '/cards/named', [
                'query' => ['fuzzy' => $cardName],
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            $cardData = $response->toArray();
            return $this->cacheCard($cardData);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Busca múltiples cartas por nombre
     */
    public function searchCardsByNames(array $cardNames): array
    {
        $cards = [];
        
        foreach ($cardNames as $name) {
            $card = $this->searchCardByName($name);
            if ($card) {
                $cards[] = $card;
            }
        }
        
        return $cards;
    }

    /**
     * Obtiene una carta que ya está cacheada
     */
    public function getCardByScryfallId(string $scryfallId): ?ScryfallCard
    {
        return $this->scryfallCardRepository->findOneBy(['scryfallId' => $scryfallId]);
    }

    /**
     * Obtiene todas las cartas cacheadas
     */
    public function getAllCachedCards(): array
    {
        return $this->scryfallCardRepository->findAll();
    }

    /**
     * Obtiene el número total de cartas en caché
     */
    public function getCacheSize(): int
    {
        return count($this->scryfallCardRepository->findAll());
    }

    /**
     * Limpia la caché (elimina todas las cartas cacheadas)
     * Úsalo con cuidado - solo en casos específicos
     */
    public function clearCache(): void
    {
        $this->scryfallCardRepository->createQueryBuilder('c')
            ->delete()
            ->getQuery()
            ->execute();
    }

    /**
     * Autocomplete for card types
     */
    public function autocompleteTypes(string $query): array
    {
        if (strlen($query) < 1) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::SCRYFALL_API . '/catalog/card-types', [
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray();
            $types = $data['data'] ?? [];
            
            // Filter types that match the query
            $filtered = array_filter($types, function($type) use ($query) {
                return stripos($type, $query) !== false;
            });
            
            return array_slice(array_values($filtered), 0, 10);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Autocomplete for artists
     */
    public function autocompleteArtists(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::SCRYFALL_API . '/catalog/artist-names', [
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray();
            $artists = $data['data'] ?? [];
            
            // Filter artists that match the query
            $filtered = array_filter($artists, function($artist) use ($query) {
                return stripos($artist, $query) !== false;
            });
            
            return array_slice(array_values($filtered), 0, 10);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get all set codes and names
     */
    public function autocompleteSets(string $query): array
    {
        if (strlen($query) < 1) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::SCRYFALL_API . '/sets', [
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray();
            $sets = $data['data'] ?? [];
            
            // Filter sets that match the query (by code or name)
            $filtered = array_filter($sets, function($set) use ($query) {
                return stripos($set['code'], $query) !== false || 
                       stripos($set['name'], $query) !== false;
            });
            
            // Map to simpler format
            $results = array_map(function($set) {
                return [
                    'code' => $set['code'],
                    'name' => $set['name'],
                ];
            }, $filtered);
            
            return array_slice(array_values($results), 0, 10);
        } catch (\Exception $e) {
            return [];
        }
    }
    public function autocompleteCardNames(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::SCRYFALL_API . '/cards/autocomplete', [
                'query' => ['q' => $query],
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray();
            return array_slice($data['data'] ?? [], 0, 5);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Search cards by name - returns multiple results
     */
    public function searchCards(string $query): array
    {
        if (strlen($query) < 2) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::SCRYFALL_API . '/cards/search', [
                'query' => ['q' => $query, 'order' => 'name'],
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            $data = $response->toArray();
            return $data['data'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Search card by ID from Scryfall
     */
    public function searchCardById(string $id): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::SCRYFALL_API . '/cards/' . $id, [
                'headers' => [
                    'User-Agent' => self::USER_AGENT,
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }

            return $response->toArray();
        } catch (\Exception $e) {
            return null;
        }
    }
    private function cacheCard(array $cardData): ScryfallCard
    {
        // Verificar si ya existe por Scryfall ID (para evitar duplicados)
        $existingCard = $this->scryfallCardRepository->findOneBy(['scryfallId' => $cardData['id']]);
        
        if ($existingCard) {
            return $existingCard;
        }

        $scryfallCard = new ScryfallCard();
        $scryfallCard->setScryfallId($cardData['id']);
        $scryfallCard->setName($cardData['name']);
        $scryfallCard->setManaCost($cardData['mana_cost'] ?? '');
        
        // Extraer la imagen con fallback
        $imageUrl = '';
        if (isset($cardData['image_uris']['normal'])) {
            $imageUrl = $cardData['image_uris']['normal'];
        } elseif (isset($cardData['card_faces'])) {
            $imageUrl = $cardData['card_faces'][0]['image_uris']['normal'] ?? '';
        }
        $scryfallCard->setImageUrl($imageUrl);
        
        $scryfallCard->setType($cardData['type_line'] ?? null);
        $scryfallCard->setCardText($cardData['oracle_text'] ?? null);
        $scryfallCard->setCardSet($cardData['set'] ?? null);
        $scryfallCard->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($scryfallCard);
        $this->entityManager->flush();

        return $scryfallCard;
    }
}