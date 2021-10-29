<?php

declare(strict_types=1);

namespace customer\services;

use InvalidArgumentException;
use core\exception\InvalidCallException;
use core\web\ServerRequest;
use core\validators\Assert;
use common\services\BaseService;
use common\filters\SearchMorph;
use customer\entities\Status;
use customer\models\suggest\Factory;
use Illuminate\Database\Eloquent\Collection;

class SearchService extends BaseService
{
    /**
     * @var array $config Fields to search and order by concrete suggesters
     */
    private array $config = [
        'production' => [
            ['translate.name', 'translate.short_name', 'translate.leader_text', 'translate.text'],
            [['on_main', 'desc'], ['order', 'desc']]
        ],
        'news' => [
            ['translate.title', 'translate.announce', 'translate.text'],
            [['on_main', 'desc'], ['created_at', 'desc']]
        ],
        'article' => [
            ['translate.title', 'translate.announce', 'translate.text'],
            ['order']
        ],
        'page' => [
            ['translate.title', 'translate.text'],
            ['order']
        ],
        'faq' => [
            ['translate.title', 'translate.question'],
            ['order']
        ],
    ];

    /**
     * @var Factory Suggesters factory
     */
    private Factory $factory;

    /**
     * Constructor.
     * 
     * @param ServerRequest $request Request object
     * @param bool $onlyActive Suggest only active entities
     */
    public function __construct(protected ServerRequest $request, private bool $onlyActive)
    {
        $this->factory = new Factory($this->request, ['search'], SearchMorph::class);
    }

    /**
     * Suggest data magic getter
     * 
     * @param string $name Search item
     * @param array $arguments
     * 
     * @return Collection Collection object contains founded models
     * 
     * @return InvalidCallException If suggester can not be resolved
     */
    public function __call(string $name, array $arguments): Collection
    {
        try {
            Assert::keyExists($this->config, $name);
            $fields = $this->config[$name][0] ?? [];
            $order = $this->config[$name][1] ?? [];
            $conditions = [];
            $suggester = $this->factory->get($name, $fields);
        } catch (InvalidArgumentException) {
            throw new InvalidCallException("Unknown method '{$name}'.");
        }

        if ($this->onlyActive) {
            $conditions[] = ['status', '=', Status::STATUS_ACTIVE];
        }

        $builder = $suggester->prepare()->where($conditions)->distinct();
        foreach ($order as $orderBy) {
            $builder->orderBy(...(array) $orderBy);
        }

        return $builder->get();
    }
}
