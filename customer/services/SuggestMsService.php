<?php

declare(strict_types=1);

namespace customer\services;

use InvalidArgumentException;
use core\exception\InvalidCallException;
use common\filters\Search;
use core\validators\Assert;
use core\web\ServerRequest;
use common\services\BaseService;
use customer\entities\Status;
use customer\models\suggest\Factory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Get data used in MagicSuggest plugin suggestions.
 * 
 * @method array article(array $fields = [], array $conditions = []) Article suggestions
 * @method array news(array $fields = [], array $conditions = []) News suggestions
 * @method array faq(array $fields = [], array $conditions = []) Faq suggestions
 * @method array page(array $fields = [], array $conditions = []) Page suggestions
 * @method array production(array $fields = [], array $conditions = []) Production suggestions
 * @method array productionCat(array $fields = [], array $conditions = []) Production category suggestions
 * @method array productionSameGroup(array $fields = [], array $conditions = []) Production same group suggestions
 * @method array productionSimilar(array $fields = [], array $conditions = []) Production similars suggestions
 * 
 * @see __call()
 */
class SuggestMsService extends BaseService
{
    /**
     * @var array $config fields to search by concrete suggesters
     */
    private array $config = [
        'article' => ['translate.title'],
        'news' => ['translate.title'],
        'faq' => ['translate.title'],
        'page' => ['translate.title'],
        'production' => ['translate.short_name'],
        'productionCat' => ['translate.name'],
        'productionSameGroup' => ['name'],
        'productionSimilar' => ['name'],
    ];

    /**
     * @var Factory Suggesters factory
     */
    private Factory $factory;

    /**
     * @param ServerRequest $request ServerRequest object
     * @param bool $onlyActive Suggest only active entities
     */
    public function __construct(private ServerRequest $request, private bool $onlyActive)
    {
        $this->factory = new Factory($this->request, ['query'], Search::class);
    }

    /**
     * Suggest data magic getter
     * 
     * @param string $name Suggester name
     * @param array $arguments Suggester constructor arguments
     * 
     * @return array Suggestion data
     * 
     * @return InvalidCallException If suggester can not be resolved
     */
    public function __call(string $name, array $arguments)
    {
        try {
            Assert::keyExists($this->config, $name);
            $fields = ($arguments[0] ?? $this->config[$name]) ?: $this->config[$name];
            $conditions = $arguments[1] ?? [];
            $suggester = $this->factory->get($name, $fields);
        } catch (InvalidArgumentException) {
            throw new InvalidCallException("Unknown method '{$name}'.");
        }

        if ($this->onlyActive) {
            $conditions[] = ['status', '=', Status::STATUS_ACTIVE];
        }

        $builder = $suggester->prepare()->where($conditions);

        return $this->mapper($builder->get(), $suggester->getAttributes()[0]);
    }

    /**
     * Mapper.
     * 
     * For MagicSuggest plugin:
     * The return data must be return a JSON encoded associative array of the format
     * [['value' => 'data1'], ['value' => 'data2'],...], where value is the fixed key set in displayKey
     * 
     * @param Builder $builder
     * @param string $query
     * @param int $limit
     * 
     * @return array
     */
    protected function mapper(Collection $collection, string $attribute): array
    {
        $result = $collection
            ->map(fn ($item) => ['id' => $item->id, 'name' => $item->$attribute])
            ->toArray();

        return $result;
    }
}
