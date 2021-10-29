<?php

declare(strict_types=1);

namespace customer\services;

use InvalidArgumentException;
use core\exception\InvalidCallException;
use core\orm\ActiveRecord;
use core\validators\Assert;
use core\helpers\ArrayHelper;
use core\web\ServerRequest;
use common\filters\Search;
use common\services\BaseService;
use customer\entities\Status;
use customer\models\suggest\Factory;
use Illuminate\Database\Eloquent\Collection;

/**
 * Get data used for search suggestions in TypeAhead plugin.
 * 
 * @method array subscribe(int $limit, array $fields = [], array $conditions = []) Subscribe suggestions
 * @method array article(int $limit, array $fields = [], array $conditions = []) Article suggestions
 * @method array news(int $limit, array $fields = [], array $conditions = []) News suggestions
 * @method array faq(int $limit, array $fields = [], array $conditions = []) Faq suggestions
 * @method array page(int $limit, array $fields = [], array $conditions = []) Page suggestions
 * @method array order(int $limit, array $fields = [], array $conditions = []) Order suggestions
 * @method array production(int $limit, array $fields = [], array $conditions = []) Production suggestions
 * @method array productionCat(int $limit, array $fields = [], array $conditions = []) Production category suggestions
 * @method array productionParam(int $limit, array $fields = [], array $conditions = []) Production parameter suggestions
 * @method array productionParamUnit(int $limit, array $fields = [], array $conditions = []) Production parameter unit suggestions
 * 
 * @see __call()
 */
class SuggestService extends BaseService
{
    /**
     * @var array $config fields to search by concrete suggesters
     */
    private array $config = [
        'subscribe' => [
            ['email', 'name', 'company', 'city', 'phone']
        ],
        'article' => [
            ['translate.title', 'translate.announce']
        ],
        'news' => [
            ['translate.title', 'translate.announce']
        ],
        'faq' => [
            ['translate.title', 'translate.question']
        ],
        'page' => [
            ['translate.title', 'translate.text']
        ],
        'order' => [
            ['name', 'email', 'phones', 'items.production.translate.short_name', 'data']
        ],
        'production' => [
            ['translate.name', 'translate.short_name', 'translate.leader_text']
        ],
        'productionCat' => [
            ['translate.name']
        ],
        'productionParam' => [
            ['translate.name']
        ],
        'productionParamUnit' => [
            ['translate.name']
        ],
    ];

    /**
     * @var string
     */
    private string $query;

    /**
     * @var Factory Suggesters factory
     */
    private Factory $factory;

    /**
     * Constructor
     * 
     * @param ServerRequest $request ServerRequest object
     * @param bool $onlyActive Suggest only active entities
     * @param int $length Suggestion length
     */
    public function __construct(protected ServerRequest $request, private bool $onlyActive, private int $length)
    {
        $this->query = $request->get('query', '');
        $this->factory = new Factory($this->request, ['query'], Search::class);
    }

    /**
     * Compose suggests from several methods
     * 
     * @param int $limit Maximum items to suggest
     * @param array $config Combine priority config. First records in config have more priority.
     * Associative array such as:
     * ```
     * $data = [
     * 'method1' => [], // method1 config
     * 'method2' => [], // method2 config
     * ];
     * ```
     * The following config fields are handled:
     *  - fields: array fields list to pass to method
     *  - keys: array pairs $name => $value to add to each item of method's suggestion data set
     * 
     * @return array
     */
    public function compose(int $limit, array $config): array
    {
        $result = [];
        foreach ($config as $method => $methodConfig) {
            $fields = ArrayHelper::remove($methodConfig, 'fields', $this->config[$method][0] ?? []);
            $keys = ArrayHelper::remove($methodConfig, 'keys', []);

            $methodLimit = $limit - count($result); // Limit for current method
            $methodResult = $this->$method($methodLimit, $fields);
            if (!$methodResult) {
                continue;
            }

            if ($keys) {
                foreach ($keys as $name => $value) {
                    $methodResult = $this->addKey($methodResult, $name, $value);
                }
            }

            $result = array_merge($result, $methodResult);
            if (count($result) === $limit) { // Break iterations if limit reached
                break;
            }
        }

        return $result;
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
    public function __call(string $name, array $arguments): array
    {
        try {
            Assert::keyExists($this->config, $name);
            $limit = $arguments[0] ?? 10;
            $fields = $arguments[1] ?? $this->config[$name][0];
            $conditions = $arguments[2] ?? [];
            $suggester = $this->factory->get($name, $fields);
        } catch (InvalidArgumentException) {
            throw new InvalidCallException("Unknown method '{$name}'.");
        }

        if ($this->onlyActive) {
            $conditions[] = ['status', '=', Status::STATUS_ACTIVE];
        }

        $builder = $suggester->prepare()->where($conditions)->limit($limit)->distinct();

        return $this->mapper($builder->get(), $suggester->getAttributes());
    }

    /**
     * Add pair $name => $value to each item of suggestion data set
     * 
     * @param array $data Suggestion data set
     * @param string $name Key name
     * @param string $value Key value
     * 
     * @return array
     */
    private function addKey(array $data, string $name, string $value): array
    {
        foreach ($data as $ind => $item) {
            $data[$ind][$name] = $value;
        }

        return $data;
    }

    /**
     * Mapper.
     * 
     * For typeahead plugin:
     * The return data for prefetch or remote in the examples below must be return a JSON encoded associative array of the format
     * [['value' => 'data1'], ['value' => 'data2'],...], where value is the fixed key set in displayKey
     * 
     * @param Collection $collection
     * @param array $attributes List of attributes definitions to use in \core\orm\Search
     * 
     * @return array
     * 
     * @see \core\orm\Search
     */
    private function mapper(Collection $collection, array $attributes = []): array
    {
        $result = $collection
            ->map(function (ActiveRecord $item) use ($attributes) {
                $value = $item->search()->findFirst($this->query, $attributes, true) ?? '';
                $formatted = $this->format($value, $this->query, $this->length);
                return ['value' => $formatted];
            })
            ->unique('value')
            ->toArray();

        return $result;
    }

    /**
     * Format suggest string:
     *  - remove all html tags
     *  - convert html entities to symbols
     *  - strip string to $length symbols
     * 
     * @param string $suggest Suggest string
     * @param string $query Find string
     * @param int $length Suggest max length
     * 
     * @return string
     */
    private function format(string $suggest, string $query, int $length): string
    {
        // If suggest is JSON encoded string (json field),
        // search first JSON attribute contains query
        try {
            Assert::json($suggest);
            $suggest = $this->extractFromJson($suggest, $query);
        } catch (InvalidArgumentException) {
        }

        $text = strip_tags($suggest);
        $text = html_entity_decode($text);
        if (mb_strlen($text) <= $length) {
            return $text;
        }

        $position = 0;
        $pos = mb_strpos($text, $query);
        if ($pos !== false) {
            $position = $pos;
        }

        return mb_strimwidth($text, $position, $length - 3, '...');
    }

    /**
     * Extract text from JSON data.
     * Decode it and search first attribute's value contains text.
     * 
     * @param string $json JSON encoded string
     * @param string $query Search string
     * 
     * @return string Text extracted from json attribute
     */
    private function extractFromJson(string $json, string $query): string
    {
        $data = (array) json_decode($json, true);
        foreach ($data as $attribute => $value) {
            $value = (string) $value;
            if (mb_stristr($value, $query)) {
                return $value;
            }
        }
    }
}
