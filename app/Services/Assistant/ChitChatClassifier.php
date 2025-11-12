<?php

namespace App\Services\Assistant;

class ChitChatClassifier
{
    public function kind(string $text): ?string
    {
        $t = mb_strtolower($text);

        if ($t === '') {
            return null;
        }

        if (preg_match('/\b(bro|bray|cuy|halo|hai|hey|yo|oi)\b/u', $t)) {
            return 'greeting';
        }

        if (preg_match('/\b(terima kasih|makasih|thanks|thx|thank you|appreciate)\b/u', $t)) {
            return 'thanks';
        }

        if (preg_match('/\b(joke|becanda|bercanda|wkwk|haha|hehe|lol)\b/u', $t)) {
            return 'joke';
        }

        if (preg_match('/\b(cuaca|panas|dingin|hujan|banjir|kualitas udara|polusi|udara|angin|gempa|kabut asap)\b/u', $t)) {
            return 'ooc';
        }

        if (preg_match('/\b(gimana kabar|apa kabar|how are you|lagi apa|what\'s up|whats up|how u doing)\b/u', $t)) {
            return 'greeting';
        }

        return null;
    }
}
