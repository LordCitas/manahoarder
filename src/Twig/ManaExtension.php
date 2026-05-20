<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ManaExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // Registramos un filtro llamado 'mana_symbols'
            new TwigFilter('mana_symbols', [$this, 'replaceManaSymbols']),
        ];
    }

    public function replaceManaSymbols(string $text): string
    {
        // Esta expresión regular busca cualquier texto encerrado entre llaves, ej: {T}, {U}, {10}, {B/G}
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) {
            // Scryfall almacena los símbolos partidos limpios sin barras oblicuas (ej: {B/G} pasa a BG.svg)
            $symbolName = str_replace('/', '', $matches[1]);
            
            // Retornamos el HTML apuntando directamente al CDN de SVGs de Scryfall
            return sprintf(
                '<img src="https://svgs.scryfall.io/card-symbols/%s.svg" class="mana-symbol" alt="%s" title="%s">',
                $symbolName,
                $matches[0],
                $matches[0]
            );
        }, $text);
    }
}