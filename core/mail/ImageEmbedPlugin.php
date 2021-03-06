<?php

declare(strict_types=1);

namespace core\mail;

use Swift_Events_SendEvent;
use Swift_Events_SendListener;
use Swift_Image;
use Swift_Mime_SimpleMimeEntity;
use Swift_Mime_SimpleMessage;

/**
 * Fixed version of Swiftmailer Image Embed Plugin:
 * @see https://github.com/Hexanet/swiftmailer-image-embed
 */
class ImageEmbedPlugin implements Swift_Events_SendListener
{
    /**
     * @var string
     */
    private $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = $basePath;
    }

    /**
     * @param Swift_Events_SendEvent $event
     */
    public function beforeSendPerformed(Swift_Events_SendEvent $event)
    {
        $message = $event->getMessage();

        // Commented by Kort.
        // if ($message->getContentType() === 'text/html') {
        //     $message->setBody($this->embedImages($message));
        // }

        /*
        Bugfix by Kort.
        There was no images embedding if attachment added after message body
        (because content type becomes to 'multipart/mixed')
        */
        $message->setBody($this->embedImages($message));

        foreach ($message->getChildren() as $part) {
            if (strpos($part->getContentType(), 'text/html') === 0) {
                $part->setBody($this->embedImages($message, $part), 'text/html');
            }
        }
    }

    /**
     * @param Swift_Events_SendEvent $event
     */
    public function sendPerformed(Swift_Events_SendEvent $event)
    {
    }

    /**
     * @param Swift_Mime_SimpleMessage $message
     * @param Swift_Mime_SimpleMimeEntity|null $part
     *
     * @return string
     */
    protected function embedImages(Swift_Mime_SimpleMessage $message, Swift_Mime_SimpleMimeEntity $part = null)
    {
        $body = $part === null ? $message->getBody() : $part->getBody();

        $dom = new \DOMDocument('1.0');
        $dom->loadHTML($body);

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $image) {
            $src = $image->getAttribute('src');

            /**
             * Prevent beforeSendPerformed called twice
             * see https://github.com/swiftmailer/swiftmailer/issues/139
             */
            if (strpos($src, 'cid:') === false) {
                $path = $this->getPathFromSrc($src);

                if ($this->fileExists($path)) {
                    $entity = Swift_Image::fromPath($path);
                    $message->setChildren(
                        array_merge($message->getChildren(), [$entity])
                    );

                    $image->setAttribute('src', 'cid:' . $entity->getId());
                }
            }
        }

        return utf8_decode($dom->saveHTML($dom->documentElement));
    }

    protected function isUrl(string $path): bool
    {
        return filter_var($path, FILTER_VALIDATE_URL) !== false;
    }

    protected function getPathFromSrc(string $src): string
    {
        if ($this->isUrl($src)) {
            return $src;
        }

        return $this->basePath . $src;
    }

    protected function fileExists(string $path): bool
    {
        if ($this->isUrl($path)) {
            return !!@getimagesize($path);
        }

        return file_exists($path);
    }
}