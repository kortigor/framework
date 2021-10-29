<?php

declare(strict_types=1);

namespace core\middleware\formatter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use core\interfaces\UnformattedResponse;

interface ResponseFormatterInterface
{
    /**
     * Format response content
     * 
     * @param UnformattedResponse $response Response with unformatted body to be formatted
     * @param StreamFactoryInterface $streamFactory Stream factory
     * 
     * @return ResponseInterface response with formatted body
     * 
     * @throws ContentNotBeFormattedException If content can not be formatted
     */
    public function format(UnformattedResponse $response, StreamFactoryInterface $streamFactory): ResponseInterface;
}