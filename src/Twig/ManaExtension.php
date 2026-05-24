<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class ManaExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // Registramos un filtro llamado 'mana_symbols' marcándolo como HTML seguro
            new TwigFilter('mana_symbols', [$this, 'replaceManaSymbols'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            // Función para renderizar un símbolo de maná individual
            new TwigFunction('mana_symbol', [$this, 'getManaSymbol'], ['is_safe' => ['html']]),
        ];
    }

    public function replaceManaSymbols(string $text): string
    {
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) {
            // Scryfall usa mayúsculas: W, U, B, R, G, C, y también números y símbolos
            // Para híbridos, quitar la barra: {2/G} → 2G, {B/R} → BR
            $symbolName = strtoupper(str_replace('/', '', $matches[1]));
            
            return sprintf(
                '<img src="https://svgs.scryfall.io/card-symbols/%s.svg" class="mana-symbol" alt="%s" title="%s">',
                $symbolName,
                $matches[0],
                $matches[0]
            );
        }, $text);
    }

    public function getManaSymbol(string $symbol, string $alt = ''): string
    {
        $symbolName = strtoupper($symbol);
        if (empty($alt)) {
            $alt = $symbol;
        }

        return sprintf(
            '<img src="https://svgs.scryfall.io/card-symbols/%s.svg" class="mana-symbol" alt="%s" title="%s" style="width:20px; height:20px; vertical-align:middle;">',
            $symbolName,
            $alt,
            $alt
        );
    }
}