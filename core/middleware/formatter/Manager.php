<?php

declare(strict_types=1);

namespace core\middleware\formatter;

use Psr\Http\Message\ResponseInterface;
use core\interfaces\UnformattedResponse;
use core\validators\Assert;
use core\http\HttpFactory;
use core\web\ContentType;

/**
 * Format response manager.
 */
class Manager
{
    const DEFAULT_CONFIG = [
        ContentType::FORMAT_HTML => [Html::class => []],
        ContentType::FORMAT_XML => [Xml::class => []],
        ContentType::FORMAT_JSON => [Json::class => [Json::DEFAULT_FLAGS]],
    ];

    protected array $config;

    /**
     * Constructor.
     * 
     * @param array $config Formatters config.
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge(static::DEFAULT_CONFIG, $config);
    }

    /**
     * Format response content.
     * 
     * @param UnformattedResponse $response Response to be formatted
     * @param string $format Format name
     * 
     * @return ResponseInterface Response with formatted content
     * @throws ContentNotBeFormattedException If content can not be formatted
     */
    public function format(UnformattedResponse $response, string $format): ResponseInterface
    {
        // Return without formatting
        if ($response->getFormat() === ContentType::FORMAT_RAW) {
            return $response;
        }

        $formatter = $this->getFormatter($format);
        $response = $formatter->format($response, new HttpFactory);
        return $response;
    }

    /**
     * Get instance of appropriate formatter according necessary response body format.
     * 
     * @param string $format
     * 
     * @return ResponseFormatterInterface
     */
    public function getFormatter(string $format): ResponseFormatterInterface
    {
        Assert::inArray($format, array_keys($this->config));
        $config = $this->config[$format];
        $class = array_keys($config)[0];
        $args = array_values($config)[0];
        Assert::implementsInterface($class, ResponseFormatterInterface::class);

        return new $class(...$args);
    }
}