<?php

namespace App\Services\Parser;

use App\Services\Logs\LoggerService;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class ArticleFinder
 *
 * Service for finding article links from HTML pages or RSS feeds.
 * Supports browser-based scraping and regex-based filtering.
 */
class ArticleFinder
{
    protected LoggerService $logger;

    protected Client $client;

    /**
     * Constructor
     *
     * @param  LoggerService  $logger  Logger service for reporting
     */
    public function __construct(LoggerService $logger)
    {
        $this->logger = $logger;

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
     * Find articles on a page using regex on link text.
     *
     * @param  string  $url  Page URL
     * @param  string  $regex  Regex to match link text
     * @param  bool  $useBrowser  Use browser-based scraping if true
     * @return array ['articles' => array, 'html' => string]
     */
    public function findArticlesByRegex(string $url, string $regex, bool $useBrowser = false): array
    {
        try {
            // Fetch HTML
            if ($useBrowser) {
                $response = Http::timeout(30)->get('http://127.0.0.1:3000/scrape', [
                    'source' => $url,
                ]);
                $html = (string) $response->body();
            } else {
                $response = $this->client->get($url);
                $html = (string) $response->getBody();
            }

            $crawler = new Crawler($html);
            $links = [];

            // Filter links by regex
            $crawler->filter('a')->each(function ($node) use (&$links, $regex, $url) {
                $text = $node->text();
                if (preg_match($regex, $text)) {
                    try {
                        $href = $node->link()->getUri();
                        // Only include links from same domain
                        if (str_contains($href, parse_url($url, PHP_URL_HOST))) {
                            $links[] = $href;
                        }
                    } catch (\Exception $e) {
                        // Ignore broken links
                    }
                }
            });

            $uniqueLinks = array_unique($links);

            $this->logger->log('ArticleFinder', 'INFO', 'Articles found successfully', [
                'url' => $url,
                'links_found' => count($uniqueLinks),
                'useBrowser' => $useBrowser,
            ]);

            return [
                'articles' => $uniqueLinks,
                'html' => $html,
            ];

        } catch (\Exception $e) {
            $this->logger->log('ArticleFinder', 'ERROR', 'Page parse error', [
                'url' => $url,
                'message' => $e->getMessage(),
                'useBrowser' => $useBrowser,
            ]);

            return [
                'articles' => [],
                'html' => '',
            ];
        }
    }

    /**
     * Find articles from an RSS feed using regex on title or description.
     *
     * @param  string  $url  RSS feed URL
     * @param  string  $regex  Regex to match title or description
     * @return array ['items' => array of ['link' => string, 'itemXml' => string]]
     */
    public function findArticlesByRss(string $url, string $regex): array
    {
        try {
            $response = $this->client->get($url);
            $xmlString = (string) $response->getBody();

            libxml_use_internal_errors(true);
            $rss = simplexml_load_string($xmlString, 'SimpleXMLElement', LIBXML_NOCDATA);

            if (! $rss) {
                return ['items' => []];
            }

            $matches = [];

            foreach ($rss->channel->item as $item) {
                $title = (string) $item->title;
                $description = (string) $item->description;
                $link = (string) $item->link;

                if (preg_match($regex, $title) || preg_match($regex, $description)) {
                    $matches[] = [
                        'link' => $link,
                        'itemXml' => $item->asXML(),
                    ];
                }
            }

            return ['items' => $matches];

        } catch (\Exception $e) {
            $this->logger->log('ArticleFinder', 'ERROR', 'RSS parse error', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return ['items' => []];
        }
    }
}
