<?php

declare(strict_types=1);

namespace customer\data;

use IteratorAggregate;
use utils\sitemap\Url;
use customer\ContentUrl;
use customer\entities\Status;
use customer\entities\Article;
use Illuminate\Support\LazyCollection;
use Illuminate\Database\Eloquent\Builder;

class SiteMapUrlProvider implements IteratorAggregate
{
    /**
     * @var array<string, mixed[]> Available content items to present links into sitemap.
     * 
     * Mixed array list values by index:
     *  - 0 (string) change frequency
     *  - 1 (float) priority
     *  - 2 (array) addttional url(s) to add to specific item urls.
     */
    private const ITEMS = [
        Article::class      => ['weekly', 0.8, ['/articles/']],
        'specials'          => ['monthly', 0.6, ['/about/', '/service/', '/contact/']],
    ];

    private const ALTERNATES = [
        ['lang' => 'en', 'host' => 'https://site.ru', 'query' => '?lang=en'],
    ];

    /**
     * IteratorAggregate implementation
     * 
     * @return iterable
     */
    public function getIterator(): iterable
    {
        foreach (self::ITEMS as $class => $record) {
            yield from $this->recordIterator($record);
            if (class_exists($class)) {
                yield from $this->collectionIterator($this->getCollection($class), $record);
            }
        }
    }

    /**
     * Get entitys lazy collection.
     * 
     * @param string $class Entity model class name
     * 
     * @return LazyCollection
     */
    protected function getCollection(string $class): LazyCollection
    {
        return $class::setEagerLoads([])
            ->whereHas('category', fn (Builder $query) => $query->where('status', Status::STATUS_ACTIVE))
            ->where('status', Status::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc')
            ->lazy();
    }

    /**
     * Item record iterator
     * 
     * @param array $record
     * 
     * @return iterable
     */
    protected function recordIterator(array $record): iterable
    {
        foreach ($record[2] ?? [] as $link) {
            yield new Url(
                path: $link,
                changeFrequency: $record[0],
                priority: $record[1],
                alternates: $this->getAlternates($link)
            );
        }
    }

    /**
     * Entities lazy collection iterator
     * 
     * @param LazyCollection $collection
     * @param array $record
     * 
     * @return iterable
     */
    protected function collectionIterator(LazyCollection $collection, array $record): iterable
    {
        foreach ($collection as $item) {
            $link = ContentUrl::to($item);
            yield new Url(
                path: $link,
                lastModified: $item->updated_at,
                changeFrequency: $record[0],
                priority: $record[1],
                alternates: $this->getAlternates($link)
            );
        }
    }

    /**
     * Generate alternates data for specified link
     * 
     * @param string $link
     * 
     * @return array
     */
    protected function getAlternates(string $link): array
    {
        return array_map(
            fn (array $alt) => ['hreflang' => $alt['lang'], 'href' => $alt['host'] . $link . $alt['query']],
            self::ALTERNATES
        );
    }
}