<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ScryfallService
{
    private HttpClientInterface $httpClient;

    // Inyectamos el HttpClient nativo de Symfony
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Busca una carta por su nombre usando coincidencia exacta o difusa (fuzzy)
     */
    public function searchCardByName(string $cardName, bool $exact = false): ?array
    {
        $param = $exact ? 'exact' : 'fuzzy';

        try {
            // Scryfall nos pide configurar un User-Agent identificable
            $response = $this->httpClient->request('GET', 'https://api.scryfall.com/cards/named', [
                'query' => [
                    $param => $cardName
                ],
                'headers' => [
                    'User-Agent' => 'ManaHoarderApp/1.0',
                    'Accept' => 'application/json',
                ]
            ]);

            if ($response->getStatusCode() === 200) {
                return $response->toArray(); // Transforma el JSON automáticamente a un array de PHP
            }
        } catch (\Exception $e) {
            // Aquí puedes registrar el error en tus logs si la carta no existe (404)
        }

        return null;
    }
}