<?php

declare(strict_types=1);

namespace App\Infrastructure\Mail;

/** Plain-text alternative for ported HTML emails that have no `.txt.twig`. */
final class HtmlToText
{
    public static function convert(string $html): string
    {
        $html = (string) preg_replace('#<(head|style|script|title)\b.*?</\1>#is', '', $html);
        $html = (string) preg_replace('#<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2 ($1)', $html);
        $html = (string) preg_replace('#<(br|/p|/div|/h[1-6]|/li|/tr)\b[^>]*>#i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = (string) preg_replace('/[ \t\x{00A0}]+/u', ' ', $text);
        $text = (string) preg_replace('/\s*\n\s*/', "\n", $text);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }
}
