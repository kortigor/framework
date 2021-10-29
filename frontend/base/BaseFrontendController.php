<?php

declare(strict_types=1);

namespace frontend\base;

use core\base\Controller;
use core\exception\HttpException;
use core\web\View;
use core\web\BlocksManager;
use core\web\Cart;
use core\web\CompareItems;

abstract class BaseFrontendController extends Controller
{
    /**
     * @var int Items per page for paginators. Retrieved from reqiest.
     */
    protected int $pageSize;

    /**
     * @var CompareItems Compare items object.
     * @see \core\middleware\CompareItemsHandle
     */
    protected CompareItems $compareItems;

    /**
     * @var Cart Cart items object.
     * @see \core\middleware\CartHandle
     */
    protected Cart $cartItems;

    /**
     * @var array Explicitly show or hide blocks.
     */
    protected array $blocks = [
        'visibleSideLeft' => true,
        'visibleSideRight' => true,
        'visibleCenterBottom' => true,
        'visibleFooterBottom' => true,
    ];

    public function __construct()
    {
        // Cart and Compare was initialized by middlewares and added to request, just get it here.
        $this->cartItems = $this->request->getAttribute('cartItems');
        $this->compareItems = $this->request->getAttribute('compareItems');
        $this->pageSize = (int) $this->request->get('items', c('site.items_per_page'));
        $this->blocks['blocks'] = c('main.siteBlocks');

        $this->view = new View('customer');
        $this->view->blocks['flashes'] = $this->flash();
        $this->view->title = c('site.site_title') ?: c('main.siteTitle');
        if ($csrfName = $this->request->getAttribute('csrfParam')) {
            $this->view->registerCsrfMetaTag($csrfName, $this->request->getAttribute($csrfName));
        }

        $this->view
            ->assign('compareItems', $this->compareItems)
            ->assign('cartItems', $this->cartItems)
            ->assign('blockManager', new BlocksManager(get_class($this), $this->blocks))
            ->registerMetaTag([
                'name' => 'description',
                'content' => c('site.site_description')
            ])
            ->registerMetaTag([
                'name' => 'keywords',
                'content' => c('site.site_keywords')
            ]);
    }

    public function actionIndex()
    {
        throw new HttpException(404, 'Страница не найдена');
    }
}