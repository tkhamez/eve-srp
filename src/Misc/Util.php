<?php

declare(strict_types=1);

namespace EveSrp\Misc;

class Util
{
    const ONE_MILLION = 1000000;

    public static function formatMillions(int $number, bool $includeM = true): string
    {
        return rtrim(rtrim(number_format($number/self::ONE_MILLION, 2), '0'), '.') . ($includeM ? 'm' : '');
    }

    public static function replaceMarkdownLink(string $text): string
    {
        // replace markdown
        $step1 = preg_replace('/\[(.*?)]\((.*?)\)/', '<a href="$2">$1</a>', $text);

        // add attributes to external links
        return preg_replace(
            '/<a href="(http.*?)">/',
            '<a href="$1" target="_blank" rel="noopener noreferrer" class="srp-external-link">',
            $step1
        );
    }
}
