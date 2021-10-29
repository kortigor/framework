<?php

declare(strict_types=1);

namespace core\data;

use Sys;
use core\helpers\Url;
use core\link\Link;
use core\link\Relations;
use core\routing\Route;
use Psr\Link\LinkProviderInterface;

/**
 * Pagination represents information relevant to pagination of data items.
 *
 * When data needs to be rendered in multiple pages, Pagination can be used to
 * represent information such as [[totalCount|total item count]], [[pageSize|page size]],
 * [[page|current page]], etc. These information can be passed to [[\core\widgets\LinkPager|pagers]]
 * to render pagination buttons or links.
 *
 * The following example shows how to create a pagination object and feed it
 * to a pager.
 *
 * Controller action:
 *
 * ```php
 * public function actionIndex()
 * {
 *     $query = Article::find()->where(['status' => 1]);
 *     $countQuery = clone $query;
 *     $pages = new Pagination(['totalCount' => $countQuery->count()]);
 *     $models = $query->offset($pages->offset)
 *         ->limit($pages->limit)
 *         ->all();
 *
 *     return $this->render('index', [
 *          'models' => $models,
 *          'pages' => $pages,
 *     ]);
 * }
 * ```
 *
 * View:
 *
 * ```php
 * foreach ($models as $model) {
 *     // display $model here
 * }
 *
 * // display pagination
 * echo LinkPager::widget([
 *     'pagination' => $pages,
 * ]);
 * ```
 *
 * For more details and usage information on Pagination, see the [guide article on pagination](guide:output-pagination).
 */
class Pagination implements LinkProviderInterface
{
    /**
     * @var string name of the parameter storing the current page index.
     * @see params
     */
    public string $pageParam = 'page';
    /**
     * @var string name of the parameter storing the page size.
     * @see params
     */
    public string $pageSizeParam = 'per-page';
    /**
     * @var bool whether to always have the page parameter in the URL created by [[createUrl()]].
     * If false and [[page]] is 0, the page parameter will not be put in the URL.
     */
    public bool $forcePageParam = true;
    /**
     * @var Route the route of the controller action for displaying the paged contents.
     * If not set, it means using the currently requested route.
     */
    public Route $route;
    /**
     * @var array parameters (name => value) that should be used to obtain the current page number
     * and to create new pagination URLs. If not set, all parameters from $_GET will be used instead.
     *
     * In order to add hash to all links use `array_merge($_GET, ['#' => 'my-hash'])`.
     *
     * The array element indexed by [[pageParam]] is considered to be the current page number (defaults to 0);
     * while the element indexed by [[pageSizeParam]] is treated as the page size (defaults to [[defaultPageSize]]).
     */
    public array $params;
    /**
     * @var bool whether to check if [[page]] is within valid range.
     * When this property is true, the value of [[page]] will always be between 0 and ([[pageCount]]-1).
     * Because [[pageCount]] relies on the correct value of [[totalCount]] which may not be available
     * in some cases (e.g. MongoDB), you may want to set this property to be false to disable the page
     * number validation. By doing so, [[page]] will return the value indexed by [[pageParam]] in [[params]].
     */
    public bool $validatePage = true;
    /**
     * @var int total number of items.
     */
    public int $totalCount = 0;
    /**
     * @var int the default page size. This property will be returned by [[pageSize]] when page size
     * cannot be determined by [[pageSizeParam]] from [[params]].
     */
    public int $defaultPageSize = 15;
    /**
     * @var array the page size limits. The first array element stands for the minimal page size, and the second
     * the maximal page size. If this is empty, it means [[pageSize]] should always return the value of [[defaultPageSize]].
     */
    public array $pageSizeLimit = [1, 50];

    /**
     * @var int number of items on each page.
     * If it is less than 1, it means the page size is infinite, and thus a single page contains all items.
     */
    private int $_pageSize;

    /**
     * @var int Zero-based current page number.
     */
    private int $_page;

    /**
     * Constructor.
     * 
     * @param array $config Array of object properties values.
     */
    public function __construct(array $config = [])
    {
        foreach ($config as $attribute => $value) {
            $this->$attribute = $value;
        }
    }

    /**
     * @return int number of pages
     */
    public function getPageCount(): int
    {
        $pageSize = $this->getPageSize();
        if ($pageSize < 1) {
            return $this->totalCount > 0 ? 1 : 0;
        }

        $totalCount = $this->totalCount < 0 ? 0 : (int) $this->totalCount;

        return (int) (($totalCount + $pageSize - 1) / $pageSize);
    }

    /**
     * Returns the zero-based current page number.
     * @param bool $recalculate whether to recalculate the current page based on the page size and item count.
     * @return int the zero-based current page number.
     */
    public function getPage(bool $recalculate = false): int
    {
        if (!isset($this->_page) || $recalculate) {
            $page = (int) $this->getQueryParam($this->pageParam, '1') - 1;
            $this->setPage($page, true);
        }

        return $this->_page;
    }

    /**
     * Sets the current page number.
     * @param int $value the zero-based index of the current page.
     * @param bool $validatePage whether to validate the page number. Note that in order
     * to validate the page number, both [[validatePage]] and this parameter must be true.
     */
    public function setPage(int $value, bool $validatePage = false): void
    {
        if ($validatePage && $this->validatePage) {
            $pageCount = $this->getPageCount();
            if ($value >= $pageCount) {
                $value = $pageCount - 1;
            }
        }
        if ($value < 0) {
            $value = 0;
        }
        $this->_page = $value;
    }

    /**
     * Returns the number of items per page.
     * By default, this method will try to determine the page size by [[pageSizeParam]] in [[params]].
     * If the page size cannot be determined this way, [[defaultPageSize]] will be returned.
     * @return int the number of items per page. If it is less than 1, it means the page size is infinite,
     * and thus a single page contains all items.
     * @see pageSizeLimit
     */
    public function getPageSize(): int
    {
        if (!isset($this->_pageSize)) {
            if (empty($this->pageSizeLimit)) {
                $pageSize = $this->defaultPageSize;
                $this->setPageSize($pageSize);
            } else {
                $pageSize = (int) $this->getQueryParam($this->pageSizeParam, (string) $this->defaultPageSize);
                $this->setPageSize($pageSize, true);
            }
        }

        return $this->_pageSize;
    }

    /**
     * @param int $value the number of items per page.
     * @param bool $validatePageSize whether to validate page size.
     */
    public function setPageSize(int $value, bool $validatePageSize = false): void
    {
        if ($validatePageSize && isset($this->pageSizeLimit[0], $this->pageSizeLimit[1]) && count($this->pageSizeLimit) === 2) {
            if ($value < $this->pageSizeLimit[0]) {
                $value = $this->pageSizeLimit[0];
            } elseif ($value > $this->pageSizeLimit[1]) {
                $value = $this->pageSizeLimit[1];
            }
        }
        $this->_pageSize = $value;
    }

    /**
     * Creates the URL suitable for pagination with the specified page number.
     * This method is mainly called by pagers when creating URLs used to perform pagination.
     * @param int $page the zero-based page number that the URL should point to.
     * @param int $pageSize the number of items on each page. If not set, the value of [[pageSize]] will be used.
     * @param bool $absolute whether to create an absolute URL. Defaults to `false`.
     * @return string the created URL
     * @see params
     * @see self::$forcePageParam
     */
    public function createUrl(int $page, int $pageSize = null, bool $absolute = false): string
    {
        $params = $this->getParams();
        if ($page > 0 || $page == 0 && $this->forcePageParam) {
            $params[$this->pageParam] = $page + 1;
        } else {
            unset($params[$this->pageParam]);
        }

        if ($pageSize <= 0) {
            $pageSize = $this->getPageSize();
        }

        if ($pageSize !== $this->defaultPageSize) {
            $params[$this->pageSizeParam] = $pageSize;
        } else {
            unset($params[$this->pageSizeParam]);
        }

        $route = $this->getRoute();
        $paramsRoute = $route->getParameters();
        if ($paramsRoute) {
            $params[0] = $route->getRule()?->getName() ?? $route->getValue();
            $params = array_merge($params, $paramsRoute);
        } else {
            $params[0] = $route->getValue();
        }

        return Url::to($params, $absolute);
    }

    /**
     * @return int the offset of the data. This may be used to set the
     * OFFSET value for a SQL statement for fetching the current page of data.
     */
    public function getOffset(): int
    {
        $pageSize = $this->getPageSize();

        return $pageSize < 1 ? 0 : $this->getPage() * $pageSize;
    }

    /**
     * @return int the limit of the data. This may be used to set the
     * LIMIT value for a SQL statement for fetching the current page of data.
     * Note that if the page size is infinite, a value -1 will be returned.
     */
    public function getLimit(): int
    {
        $pageSize = $this->getPageSize();

        return $pageSize < 1 ? -1 : $pageSize;
    }

    /**
     * {@inheritDoc}
     * 
     * Returns a whole set of links for navigating to the first, last, next and previous pages.
     * @param bool $absolute whether the generated URLs should be absolute.
     * @return array the links for navigational purpose. The array keys specify the purpose of the links (e.g. [[LINK_FIRST]]),
     * and the array values are the corresponding URLs.
     */
    public function getLinks(bool $absolute = false): iterable
    {
        $currentPage = $this->getPage();
        $pageCount = $this->getPageCount();
        yield new Link(Relations::REL_SELF, $this->createUrl($currentPage, null, $absolute));

        if ($currentPage > 0) {
            yield new Link(Relations::REL_FIRST, $this->createUrl(0, null, $absolute));
            yield new Link(Relations::REL_PREV, $this->createUrl($currentPage - 1, null, $absolute));
        }

        if ($currentPage < $pageCount - 1) {
            yield new Link(Relations::REL_NEXT, $this->createUrl($currentPage + 1, null, $absolute));
            yield new Link(Relations::REL_LAST, $this->createUrl($pageCount - 1, null, $absolute));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getLinksByRel(string $rel): iterable
    {
        /** @var \Psr\Link\LinkInterface $link */
        foreach ($this->getLinks() as $link) {
            if ($link->getRels() === $rel) {
                yield $link;
            }
        }
    }

    /**
     * Returns the value of the specified query parameter.
     * This method returns the named parameter value from [[params]]. Null is returned if the value does not exist.
     * @param string $name the parameter name
     * @param string $default the value to be returned when the specified parameter does not exist in [[params]].
     * @return string the parameter value
     */
    protected function getQueryParam(string $name, string $default = null): ?string
    {
        $params = $this->getParams();

        return isset($params[$name]) && is_scalar($params[$name]) ? $params[$name] : $default;
    }

    /**
     * Get query parameters.
     * 
     * @return array
     * @see self::$params
     */
    protected function getParams(): array
    {
        if (!isset($this->params)) {
            $this->params = Sys::$app->getController()->getRequest()->getQueryParams();
        }

        return $this->params;
    }

    /**
     * Get route of the controller action for displaying the paged contents.
     * 
     * @return Route
     * @see self::$route
     */
    protected function getRoute(): Route
    {
        if (!isset($this->route)) {
            $this->route = Sys::$app->getRoute();
        }

        return $this->route;
    }
}