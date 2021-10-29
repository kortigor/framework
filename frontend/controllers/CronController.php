<?php

declare(strict_types=1);

namespace frontend\controllers;

use customer\data\SiteMapUrlProvider;
use core\helpers\Url;
use core\web\ContentType;
use utils\sitemap\Generator;
use utils\sitemap\Robots;
use utils\sitemap\Submitter;

class CronController extends \core\base\Controller
{
    public function __construct()
    {
    }

    public function actionSitemap()
    {
        $this->response->setFormat(ContentType::FORMAT_JSON);

        $result = [
            'title' => 'Sitemap generator task result',
            'compression' => false,
            'update_robots' => false,
            'success' => false,
        ];

        $baseUrl = Url::getShemeHost(Url::$uri);
        $basePath = fsPath('/frontend/web/');
        $generator = new Generator($baseUrl, $basePath);

        // Create a compressed sitemap
        if ($this->request->get('compressed')) {
            $generator->enableCompression();
            $result['compression'] = true;
        }

        foreach (new SiteMapUrlProvider as $url) {
            $generator->addURL($url);
        }

        $generator->generate();

        // Update robots
        if ($this->request->get('robots')) {
            $updater = new Robots($generator);
            if ($updater->update() !== false) {
                $result['update_robots'] = true;
            }
        }

        // Submit to search engines
        if ($this->request->get('submit')) {
            $submitter = new Submitter($generator);
            $result['submitted'] = $submitter->submit();
        }

        $result['success'] = true;
        $result['files'] = $generator->getGeneratedFiles();

        return $result;
    }
}
