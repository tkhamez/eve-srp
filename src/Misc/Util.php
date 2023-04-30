<?php

declare(strict_types=1);

namespace EveSrp\Misc;

class Util
{
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
