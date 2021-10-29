<?php

declare(strict_types=1);

namespace utils\sitemap;

use DateTime;
use InvalidArgumentException;

/**
 * Sitemap url representation.
 */
class Url
{
    /**
     * @var int Max url length according to spec.
     * @see https://www.sitemaps.org/protocol.html#xmlTagDefinitions
     */
    private const MAX_URL_LEN = 2048;

    /**
     * @var string[] List of valid changefreq values according to the spec.
     * @see https://www.sitemaps.org/protocol.html#xmlTagDefinitions
     */
    private const VALID_CHANGE_FREQ = [
        'always',
        'hourly',
        'daily',
        'weekly',
        'monthly',
        'yearly',
        'never',
    ];

    /**
     * @var float[] List of valid priority values according to the spec.
     * @see https://www.sitemaps.org/protocol.html#xmlTagDefinitions
     */
    private const VALID_PRIORITIES = [
        0.0,
        0.1,
        0.2,
        0.3,
        0.4,
        0.5,
        0.6,
        0.7,
        0.8,
        0.9,
        1.0,
    ];

    /**
     * Constructor.
     */
    public function __construct(
        public string $path,
        public ?DateTime $lastModified = null,
        public string $changeFrequency = '',
        public ?float $priority = null,
        public array $alternates = [],
    ) {
        $this->assertValid();
    }

    /**
     * Assert object is valid.
     * 
     * @return void
     */
    protected function assertValid()
    {
        if (mb_strlen($this->path) <= 1 || mb_strlen($this->path) >= self::MAX_URL_LEN) {
            throw new InvalidArgumentException(sprintf("Url path length must be between 1 and %d.", self::MAX_URL_LEN));
        }

        if ($this->changeFrequency && !in_array($this->changeFrequency, self::VALID_CHANGE_FREQ)) {
            throw new InvalidArgumentException('Change frequency should be one of: ' . implode(',', self::VALID_CHANGE_FREQ));
        }

        if ($this->priority !== null && !in_array($this->priority, self::VALID_PRIORITIES)) {
            throw new InvalidArgumentException('Priority should be a float number in the range [0.0..1.0].');
        }

        foreach ($this->alternates as $alternate) {
            if (!is_array($alternate) || !isset($alternate['hreflang']) || !isset($alternate['href'])) {
                throw new InvalidArgumentException("Alternates should be an array and have 'hreflang' and 'href' elements");
            }
        }
    }
}