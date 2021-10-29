<?php

declare(strict_types=1);

namespace core\middleware\formatter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use core\interfaces\UnformattedResponse;
use SimpleXMLElement;

final class Xml implements ResponseFormatterInterface
{
    /**
     * {@inheritDoc}
     */
    public function format(UnformattedResponse $response, StreamFactoryInterface $streamFactory): ResponseInterface
    {
        $xml = $this->formatContent($response->getBodyData());
        $response = $response
            ->withHeader('Content-Type', 'text/xml; charset=' . $response->charset)
            ->withBody($streamFactory->createStream($xml));

        $response = (new NoCache())->format($response, $streamFactory);

        return $response;
    }

    private function formatContent(SimpleXMLElement $content): string
    {
        $xml = $content->asXML();
        if ($xml === false) {
            throw new ContentNotBeFormattedException('Invalid SimpleXMLElement object');
        }

        return $xml;
    }
}