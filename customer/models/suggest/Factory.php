<?php

declare(strict_types=1);

namespace customer\models\suggest;

use core\web\ServerRequest;
use core\validators\Assert;
use common\filters\Search;

/**
 * Suggesters factory.
 */
class Factory
{
    /**
     * Constructor
     *
     * @param ServerRequest $request Server request object instance
     * @param array $params Parameter to handle suggest/search
     * @param string $classFilter Model filter fully qualified class name to use
     *
     * @return void
     * @throws \InvalidArgumentException If filter class does not exists or not `\common\filters\Search` type.
     * 
     * @see \common\filters\Search
     */
    public function __construct(private ServerRequest $request, private array $params, private string $classFilter)
    {
        Assert::isAOf($classFilter, Search::class);
    }

    /**
     * Get needed suggester.
     * 
     * @param string $name Suggester name
     * @param array $fields Fields to search in
     * 
     * @return SuggestAbstract The suggest search object instance
     * @throws \InvalidArgumentException If suggester class does not exists or not SuggestAbstract type.
     */
    public function get(string $name, array $fields): SuggestAbstract
    {
        $class = sprintf('%s\%sSuggest', __NAMESPACE__, ucfirst($name));
        Assert::isAOf($class, SuggestAbstract::class);
        $classFilter = $this->classFilter;
        $filter = new $classFilter($this->request, $fields, $this->params);

        return new $class($filter, $fields);
    }
}
