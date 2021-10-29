<?php

declare(strict_types=1);

namespace customer;

use core\interfaces\ContentUrlInterface;
use core\orm\ActiveRecord;
use core\helpers\Url;

/**
 * Generate content url of general entities.
 * For frontend application.
 */
final class ContentUrl
{
    /**
     * @var string Namespace of content url options objects.
     */
    private const URL_NAMESPACE = __NAMESPACE__ . '\urls\\';

    /**
     * @var array Url generating options
     * @see Url::to()
     */
    private array $options;

    /**
     * Constructor.
     * 
     * @param ActiveRecord $entity Content entity to generate url
     */
    public function __construct(private ActiveRecord $entity)
    {
        $url = $this->createObject();
        $this->options = $url->getUrlOptions();
        if (APP !== 'frontend') {
            FrontendUrlGenerator::load();
            $this->options['@home'] = '';
        }
    }

    /**
     * Generate frontend url to given content.
     * 
     * @param ActiveRecord $entity Content entity.
     * @param bool $absolute Generate absolute url with scheme and host.
     * @param array $options Additional url options.
     * 
     * @return string Generated url
     * @see getUrl()
     */
    public static function to(ActiveRecord $entity, bool $absolute = false, array $options = []): string
    {
        return (new self($entity))->getUrl($absolute, $options);
    }

    /**
     * Get generated content url.
     * 
     * @param bool $absolute Generate absolute url with scheme and host
     * @param array $options Additional url options.
     * 
     * @return string Generated url
     * @see Url::to()
     */
    public function getUrl(bool $absolute, array $options): string
    {
        $options = array_merge($this->options, $options);
        return Url::to($options, $absolute);
    }

    /**
     * Create url options object.
     * 
     * @return ContentUrlInterface
     */
    private function createObject(): ContentUrlInterface
    {
        $class = self::URL_NAMESPACE . get_class_short($this->entity);
        return new $class($this->entity);
    }
}
