<?php

declare(strict_types=1);

namespace core\middleware\formatter;

use Throwable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use core\interfaces\UnformattedResponse;

final class Json implements ResponseFormatterInterface
{
    const DEFAULT_FLAGS = JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE;

    const EXTENDED_FLAGS = self::DEFAULT_FLAGS | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP;

    const CONTENT_TYPE = 'application/json; charset=UTF-8';

    /**
     * Constructor.
     * 
     * @param int $flags Json formatting flags.
     */
    public function __construct(private int $flags)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function format(UnformattedResponse $response, StreamFactoryInterface $streamFactory): ResponseInterface
    {
        $json = $this->formatContent($response->getBodyData());
        $response = $response
            ->withHeader('Content-Type', static::CONTENT_TYPE)
            ->withBody($streamFactory->createStream($json));

        $response = (new NoCache)->format($response, $streamFactory);

        return $response;
    }

    private function formatContent($content): string
    {
        try {
            return json_encode($content, $this->flags | JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            throw new ContentNotBeFormattedException(
                sprintf('An exception was thrown during JSON formatting: %s', $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
}