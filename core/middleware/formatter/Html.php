<?php

declare(strict_types=1);

namespace core\middleware\formatter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use core\interfaces\UnformattedResponse;

final class Html implements ResponseFormatterInterface
{
    /**
     * {@inheritDoc}
     */
    public function format(UnformattedResponse $response, StreamFactoryInterface $streamFactory): ResponseInterface
    {
        $data = $response->getBodyData() ?? '';
        $type = gettype($data);
        if ($type !== 'string') {
            throw new ContentNotBeFormattedException("Html body data MUST be a string, {$type} given");
        }

        $response = $response
            ->withHeader('Content-Type', 'text/html; charset=' . $response->getCharset())
            ->withBody($streamFactory->createStream($data ?? ''));

        return $response;
    }
}