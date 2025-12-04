<?php

namespace App\Constants;

/**
 * Class ParsingConstants
 *
 * Central repository for all parsing-related constants.
 * Eliminates magic numbers and hardcoded values across the application.
 *
 * Usage:
 *   if ($link->attempts >= ParsingConstants::MAX_RETRY_ATTEMPTS) { ... }
 *   if ($type === ParsingConstants::TYPE_HTML) { ... }
 */
class ParsingConstants
{
    // ============ RETRY & LIMITS ============

    /**
     * Maximum number of retry attempts for processing a link
     */
    public const MAX_RETRY_ATTEMPTS = 3;

    /**
     * Default timeout for article content parsing (seconds)
     */
    public const PARSER_TIMEOUT = 15;

    /**
     * Timeout for browser-based parsing with Puppeteer (seconds)
     */
    public const BROWSER_TIMEOUT = 30;

    // ============ LINK STATUSES ============

    /**
     * Link has not been parsed yet
     */
    public const STATUS_UNPARSED = 0;

    /**
     * Link has been successfully parsed
     */
    public const STATUS_PARSED = 1;

    // ============ SOURCE TYPES ============

    /**
     * Source type: HTML page (requires parsing)
     */
    public const TYPE_HTML = 'html';

    /**
     * Source type: RSS feed
     */
    public const TYPE_RSS = 'rss';

    // ============ ARTICLE SOURCES ============

    /**
     * Article source: HTML parsing
     */
    public const SOURCE_HTML = 'html';

    /**
     * Article source: RSS feed
     */
    public const SOURCE_RSS = 'rss';

    // ============ EMBEDDING & DUPLICATES ============

    /**
     * Default similarity threshold for duplicate detection (0-1)
     * Articles with similarity >= this value are considered duplicates
     */
    public const DUPLICATE_THRESHOLD = 0.85;

    // ============ LOGGING ============

    /**
     * Log level: Information
     */
    public const LOG_INFO = 'INFO';

    /**
     * Log level: Warning
     */
    public const LOG_WARNING = 'WARNING';

    /**
     * Log level: Error
     */
    public const LOG_ERROR = 'ERROR';

    /**
     * Log level: Debug
     */
    public const LOG_DEBUG = 'DEBUG';

    // ============ COMMAND NAMES ============

    /**
     * Console command name for parsing links (Stage 1)
     */
    public const COMMAND_PARSE_LINKS = 'ConsoleParseLinks';

    /**
     * Console command name for parsing articles (Stage 2)
     */
    public const COMMAND_PARSE_ARTICLES = 'ConsoleParseArticles';

    // ============ ERROR TRACKING ============

    /**
     * Error type: Parsing error
     */
    public const ERROR_PARSING = 'parsing_error';

    /**
     * Error type: Network error
     */
    public const ERROR_NETWORK = 'network_error';

    /**
     * Error type: Timeout error
     */
    public const ERROR_TIMEOUT = 'timeout_error';

    /**
     * Error type: Service unavailable
     */
    public const ERROR_SERVICE = 'service_unavailable';

    // ============ SENTIMENT & EMOTIONS ============

    /**
     * Sentiment: Positive
     */
    public const SENTIMENT_POSITIVE = 'positive';

    /**
     * Sentiment: Negative
     */
    public const SENTIMENT_NEGATIVE = 'negative';

    /**
     * Sentiment: Neutral
     */
    public const SENTIMENT_NEUTRAL = 'neutral';

    /**
     * Get all available sentiments
     */
    public static function sentiments(): array
    {
        return [
            self::SENTIMENT_POSITIVE,
            self::SENTIMENT_NEGATIVE,
            self::SENTIMENT_NEUTRAL,
        ];
    }

    /**
     * Get all available source types
     */
    public static function sourceTypes(): array
    {
        return [
            self::TYPE_HTML,
            self::TYPE_RSS,
        ];
    }

    /**
     * Get all available article sources
     */
    public static function articleSources(): array
    {
        return [
            self::SOURCE_HTML,
            self::SOURCE_RSS,
        ];
    }

    /**
     * Get all log levels
     */
    public static function logLevels(): array
    {
        return [
            self::LOG_INFO,
            self::LOG_WARNING,
            self::LOG_ERROR,
            self::LOG_DEBUG,
        ];
    }

    /**
     * Get all error types
     */
    public static function errorTypes(): array
    {
        return [
            self::ERROR_PARSING,
            self::ERROR_NETWORK,
            self::ERROR_TIMEOUT,
            self::ERROR_SERVICE,
        ];
    }

    /**
     * Check if a given status is valid
     */
    public static function isValidStatus(int $status): bool
    {
        return in_array($status, [self::STATUS_UNPARSED, self::STATUS_PARSED], true);
    }

    /**
     * Check if a given sentiment is valid
     */
    public static function isValidSentiment(string $sentiment): bool
    {
        return in_array($sentiment, self::sentiments(), true);
    }

    /**
     * Check if a given source type is valid
     */
    public static function isValidSourceType(string $type): bool
    {
        return in_array($type, self::sourceTypes(), true);
    }
}
