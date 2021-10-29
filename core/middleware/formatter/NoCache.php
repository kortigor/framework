<?php

declare(strict_types=1);

namespace core\middleware\formatter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use core\interfaces\UnformattedResponse;

/**
 * Adds only headers to prevent page caching by browser.
 */
final class NoCache implements ResponseFormatterInterface
{
    /**
     * {@inheritDoc}
     */
    public function format(UnformattedResponse $response, StreamFactoryInterface $streamFactory): ResponseInterface
    {
        return $response
            ->withHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT')
            ->withHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withAddedHeader('Cache-Control', 'post-check=0, pre-check=0')
            ->withHeader('Pragma', 'no-cache');
    }
}