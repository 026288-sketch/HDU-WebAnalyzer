<?php

namespace App\Services\Parser;

/**
 * Class HashService
 *
 * Service for generating a unique hash for an article based on its title and content.
 */
class HashService
{
    /**
     * Generate a SHA-256 hash from the title and content of an article.
     *
     * @param  string  $title  Article title
     * @param  string  $content  Article content
     * @return string SHA-256 hash
     */
    public static function generate(string $title, string $content): string
    {
        return hash('sha256', $title.$content);
    }
}
