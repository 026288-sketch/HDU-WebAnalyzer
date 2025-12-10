<?php

namespace App\Services\Parser;

use App\Services\Logs\LoggerService;
use fivefilters\Readability\Configuration;
use fivefilters\Readability\Readability;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

/**
 * Class ArticleContentParser
 *
 * Service for parsing the full HTML content of articles and RSS items.
 * Supports both direct HTTP requests and browser-based scraping.
 * Cleans up HTML, extracts main content, title, images, summary, and publication date.
 */
class ArticleContentParser
{
    protected LoggerService $logger;

    protected Client $client;

    protected string $defaultImage = '/images/default.png';

    /**
     * Constructor
     *
     * @param  LoggerService  $logger  Logger service for error reporting
     */
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;

        // Guzzle client configuration
        $this->client = new Client([
            'timeout' => 15,
            'decode_content' => false,
            'headers' => [
                'Accept-Encoding' => 'identity',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.86 Safari/537.36',
            ],
        ]);
    }

    /**
     * Parse the HTML content of an article.
     *
     * @param  string  $url  Article URL
     * @param  bool  $useBrowser  Use browser-based scraping if true
     * @return array|null Parsed data or null if failed
     *
     * @throws \Exception
     */
    public function parseHtml(string $url, bool $useBrowser = false): ?array
    {
        try {
            // Fetch HTML content
            if ($useBrowser)
                $response = Http::timeout(30)->get(env('PUPPETEER_URL', 'http://puppeteer:3000') . '/scrape', [
                   'source' => $url,
                ]);

                $html = (string) $response->body();

                if (! $html) {
                    $this->logger->log('ArticleContentParser', 'ERROR', 'Browser did not return content', [
                        'url' => $url,
                    ]);

                    return null;
                }
            } else {
                $response = $this->client->get($url);
                $html = (string) $response->getBody();
            }

            // Clean HTML before parsing (remove unnecessary tags but keep meta)
            $html = $this->cleanHtmlBeforeParsing($html);

            // Parse using Readability
            $config = new Configuration;
            $readability = new Readability($config);
            $readability->parse($html);

            $title = $readability->getTitle();
            $content = $readability->getContent();
            $excerpt = $readability->getExcerpt();
            $image = $readability->getImage() ?? $this->defaultImage;
            $publishedAt = $this->extractDateFromPage($html) ?? now()->toDateTimeString();

            return [
                'title' => $title,
                'content' => $content,
                'summary' => $excerpt,
                'image' => $image,
                'url' => $url,
                'timestamp' => $publishedAt,
                'html' => $html,
            ];
        } catch (\Exception $e) {
            $this->logger->log('ArticleContentParser', 'ERROR', 'Parse error', [
                'url' => $url,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Clean HTML by removing scripts, styles, iframes, etc.
     *
     * @return string Cleaned HTML
     */
    protected function cleanHtmlBeforeParsing(string $html): string
    {
        libxml_use_internal_errors(true);

        $dom = new \DOMDocument;
        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($dom);

        $removeXPaths = [
            '//script', '//style', '//noscript', '//iframe', '//form', '//link',
        ];

        foreach ($removeXPaths as $xp) {
            $nodes = $xpath->query($xp);
            foreach ($nodes as $n) {
                if ($n->parentNode) {
                    $n->parentNode->removeChild($n);
                }
            }
        }

        $clean = $dom->saveHTML();
        $clean = preg_replace('/[\r\n]+/u', "\n", $clean);
        $clean = preg_replace('/\s{2,}/u', ' ', $clean);
        $clean = trim($clean);

        libxml_clear_errors();

        return $clean;
    }

    /**
     * Clean HTML specifically for RSS content.
     */
    protected function cleanHtmlForRss(string $html): string
    {
        $allowedTags = '<p><ul><ol><li><table><tr><td><th><blockquote><strong><em><b><i><br>';
        $html = html_entity_decode($html, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $html = strip_tags($html, $allowedTags);
        $html = preg_replace('/[\r\n]+/u', "\n", $html);
        $html = preg_replace('/\s{2,}/u', ' ', $html);

        return trim($html);
    }

    /**
     * Extract publication date from HTML.
     */
    protected function extractDateFromPage(string $html): ?string
    {
        libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($doc);

        // Try meta property
        $meta = $xpath->query('//meta[@property="article:published_time"]')->item(0);
        if ($meta && $meta->getAttribute('content')) {
            return date('Y-m-d H:i:s', strtotime($meta->getAttribute('content')));
        }

        // Try <time datetime>
        $timeTag = $xpath->query('//time[@datetime]')->item(0);
        if ($timeTag) {
            return date('Y-m-d H:i:s', strtotime($timeTag->getAttribute('datetime')));
        }

        return null;
    }

    /**
     * Parse a single RSS item XML.
     *
     * @throws \Exception
     */
    public function parseRss(string $itemXml): ?array
    {
        try {
            libxml_use_internal_errors(true);

            $wrappedXml = '<root xmlns:content="http://purl.org/rss/1.0/modules/content/" '
                        .'xmlns:media="http://search.yahoo.com/mrss/">'
                        .$itemXml.
                        '</root>';

            $xml = simplexml_load_string($wrappedXml, 'SimpleXMLElement', LIBXML_NOCDATA);
            if (! $xml) {
                return null;
            }

            // Extract fields using XPath
            $titleNodes = $xml->xpath('//title');
            $linkNodes = $xml->xpath('//link');
            $descriptionNodes = $xml->xpath('//description');
            $contentNodes = $xml->xpath('//content:encoded');
            $fullTextNodes = $xml->xpath('//full-text');
            $mediaNodes = $xml->xpath('//media:content');
            $pubDateNodes = $xml->xpath('//pubDate');

            $title = isset($titleNodes[0]) ? strip_tags(html_entity_decode((string) $titleNodes[0], ENT_QUOTES | ENT_XML1)) : null;
            $url = isset($linkNodes[0]) ? (string) $linkNodes[0] : null;
            $excerpt = isset($descriptionNodes[0]) ? strip_tags(html_entity_decode((string) $descriptionNodes[0], ENT_QUOTES | ENT_XML1)) : '';

            $rawContent = $contentNodes[0] ?? $fullTextNodes[0] ?? $descriptionNodes[0] ?? '';
            $rawContent = (string) $rawContent;
            $content = $this->cleanHtmlForRss($rawContent);

            // Extract image
            $image = $this->extractImageFromRss($xml, $mediaNodes, $content);

            $publishedAt = isset($pubDateNodes[0])
                ? date('Y-m-d H:i:s', strtotime((string) $pubDateNodes[0]))
                : now()->toDateTimeString();

            if (! $title || ! $url || ! $content) {
                return null;
            }

            return [
                'title' => $title,
                'content' => $content,
                'summary' => $excerpt,
                'image' => $image,
                'url' => $url,
                'timestamp' => $publishedAt,
            ];

        } catch (\Exception $e) {
            $this->logger->log('ArticleContentParser', 'ERROR', 'RSS parse error', [
                'item' => $itemXml,
                'exception' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Extract first image from HTML string.
     */
    protected function extractImageFromHtml(?string $html): ?string
    {
        if (! $html) {
            return null;
        }

        libxml_use_internal_errors(true);
        $doc = new \DOMDocument;
        $doc->loadHTML($html);
        $img = $doc->getElementsByTagName('img')->item(0);

        return $img ? $img->getAttribute('src') : null;
    }

    /**
     * Helper to extract image for RSS items.
     */
    protected function extractImageFromRss(\SimpleXMLElement $xml, ?array $mediaNodes, string $content): string
    {
        $image = null;

        // 1. enclosure with image
        $enclosureNodes = $xml->xpath('//enclosure[@url]');
        foreach ($enclosureNodes as $enclosure) {
            $imgUrl = (string) $enclosure['url'];
            if (preg_match('/\.(jpe?g|png|webp)$/i', $imgUrl)) {
                $image = $imgUrl;
                break;
            }
        }

        // 2. media:content
        if (! $image && $mediaNodes) {
            foreach ($mediaNodes as $media) {
                $type = (string) $media['type'];
                if (str_starts_with($type, 'image')) {
                    $image = (string) $media['url'];
                    break;
                }
            }
        }

        // 3. first image in content
        if (! $image) {
            $image = $this->extractImageFromHtml($content) ?? $this->defaultImage;
        }

        return $image;
    }
}
