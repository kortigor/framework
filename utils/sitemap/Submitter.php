<?php

declare(strict_types=1);

namespace utils\sitemap;

use BadMethodCallException;

/**
 * Sitamap file submitter
 */
class Submitter
{
    /**
     * @var array Search engines URLs
     */
    private array $searchEngines = [
        'https://www.google.com/ping?sitemap=',
        'https://www.bing.com/ping?sitemap=',
        'http://www.webmaster.yandex.ru/ping?sitemap=',
    ];

    /**
     * Constructor.
     * 
     * @param Generator $generator Sitemap generator instance.
     */
    public function __construct(private Generator $generator)
    {
    }

    /**
     * Inform search engines about newly created sitemaps.
     * Google, Bing and Yandex will be noticed.
     * 
     * @return array of messages and http codes from each search engine
     * @throws BadMethodCallException
     */
    public function submit(): array
    {
        $files = $this->generator->getGeneratedFiles();
        if (!$files) {
            throw new BadMethodCallException('To submit sitemap, call Generator::generate() first.');
        }

        if (!extension_loaded('curl')) {
            throw new BadMethodCallException('cURL extension is needed to do submission.');
        }

        $result = [];
        foreach ($this->searchEngines as $engineUrl) {
            $submitUrl = $engineUrl . htmlspecialchars($files['sitemaps_index_url'], ENT_QUOTES);
            $submitSiteShort = array_reverse(explode(".", parse_url($engineUrl, PHP_URL_HOST)));
            $submitSite = curl_init($submitUrl);
            curl_setopt($submitSite, CURLOPT_RETURNTRANSFER, true);
            $responseContent = curl_exec($submitSite);
            $response = curl_getinfo($submitSite);
            $message = $responseContent === false ? 'Runtime error' : str_replace("\n", " ", strip_tags($responseContent));
            $result[] = [
                'site' => $submitSiteShort[1] . "." . $submitSiteShort[0],
                'fullsite' => $submitUrl,
                'http_code' => $response['http_code'],
                'message' => $message,
            ];
        }

        return $result;
    }
}