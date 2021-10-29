<?php

declare(strict_types=1);

declare(strict_types=1);

namespace common\widgets;

use core\bootstrap4\Widget;
use core\bootstrap4\NavLinkButtonGroup;
use core\bootstrap4\LinkPager;
use core\data\Pagination;
use core\helpers\Url;
use core\helpers\Html;
use core\http\Uri;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Entities index paginator widget
 */
class SitePaginator extends Widget
{
    /**
     * @var LengthAwarePaginator Paginated items collection.
     */
    public LengthAwarePaginator $collection;

    /**
     * @var int[] Available items per page
     */
    public array $itemsPerPage = [15, 30, 60, 90];

    /**
     * @var string Items per page query parameter
     */
    public string $itemsPerPageParam = 'items';

    /**
     * @var string
     */
    public $containerClass = '';

    /**
     * @var string
     */
    public string $signBeforePageSizer = 'Показывать на странице';

    /**
     * @var Uri
     */
    private Uri $baseUri;

    /**
     * Renders the widget.
     * 
     * @throws InvalidConfigException
     */
    public function run()
    {
        // Ensure all itemsPerPage is integer type
        $this->itemsPerPage = array_map(
            fn ($item) => (int) $item,
            $this->itemsPerPage
        );

        // Don't show paginator if total pages count less or equal than number of the first "per page" element
        if ($this->collection->total() <= ($this->itemsPerPage[0] ?? -1)) {
            return '';
        }

        $pagination = new Pagination([
            'forcePageParam' => false,
            'pageSizeParam' => '',
            'totalCount' => $this->collection->total(),
            'defaultPageSize' => $this->collection->perPage(),
            'pageParam' => $this->collection->getPageName(),
            'pageSizeLimit' => [], // Reset page size limit
        ]);

        $pager = LinkPager::widget([
            'pagination' => $pagination,
            'lastPageLabel' => true,
            'firstPageLabel' => true,
            'listOptions' => [
                'class' => 'pagination justify-content-center justify-content-md-end my-0'
            ]
        ]);

        if ($itemsPerPage = $this->getItemsPerPage()) {
            $sizer = NavLinkButtonGroup::widget([
                'items' => $itemsPerPage,
                'buttonsClass' => 'btn btn btn-outline-primary paginator-items-per-page',
                'encodeLabels' => false,
                'options' => [
                    'class' => 'btn-group',
                    'tag' => 'nav',
                ]
            ]);

            $text = $this->signBeforePageSizer ? $this->signBeforePageSizer . ': ' : '';
        } else {
            $sizer = '';
            $text = '';
        }

        $sizerHtml = Html::tag('div', $text . $sizer, ['class' => 'col-md-5 mb-3 mb-md-0 text-nowrap text-center text-md-left']);
        $pagerHtml = Html::tag('div', $pager, ['class' => 'col-md-7 mb-3 mb-md-0']);
        $content = Html::tag('div', $sizerHtml . $pagerHtml, ['class' => 'row']);

        return Html::tag('div', $content, ['class' => $this->containerClass]);
    }

    private function baseUri(): Uri
    {
        if (!isset($this->baseUri)) {
            $uri = Uri::withOutQueryValue(Url::$uri, $this->itemsPerPageParam);
            $uri = Uri::withOutQueryValue($uri, $this->collection->getPageName());
            $this->baseUri = $uri;
        }
        return $this->baseUri;
    }

    private function getItemsPerPage(): array
    {
        $items = [];
        foreach ($this->itemsPerPage as $i => $numItems) {
            $item = [
                'label' => (string) $numItems,
            ];

            if ($i === 0) {
                $url = Uri::withOutQueryValue($this->baseUri(), $this->itemsPerPageParam);
                $item['active'] = Url::getQueryValue($this->itemsPerPageParam) === null;
            } else {
                $url = Uri::withQueryValue($this->baseUri(), $this->itemsPerPageParam, (string) $numItems);
            }

            $item['url'] = Url::getRelative($url);
            $items[] = $item;
        }

        return $items;
    }
}