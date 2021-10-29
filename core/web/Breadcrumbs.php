<?php

declare(strict_types=1);

namespace core\web;

use core\helpers\Url;
use core\helpers\Html;

class Breadcrumbs
{
	public static string $homeLabel = '<i class="fa fa-fw fa-home"></i>';

	private static array $crumbs = [];

	private static string $html = '';

	private static bool $activeLastLink = false;

	/**
	 * Render breadcrumbs
	 * @param mixed array
	 * 
	 * @return string breadcrumbs html code
	 */
	public static function render(array $options = ['class' => 'breadcrumb']): string
	{
		array_unshift(static::$crumbs, static::getHomeCrumb());
		$numCrumbs = count(static::$crumbs);

		foreach (self::$crumbs as $ind => $val) {
			if ($numCrumbs === ($ind + 1) && !static::$activeLastLink) {
				$crumbContent = $val['label'];
				$crumbClass = 'breadcrumb-item active';
			} else {
				$crumbContent = Html::tag('a', $val['label'], ['href' => $val['link']]);
				$crumbClass = 'breadcrumb-item';
			}
			static::$html .= Html::tag('li', $crumbContent, ['class' => $crumbClass]);
		}
		$list = Html::tag('ol', static::$html, $options);

		return Html::tag('nav', $list, ['aria-label' => 'breadcrumb']);
	}

	/**
	 * Add breadcrumb
	 * 
	 * @param string $label
	 * @param string $url
	 * @param bool $encode
	 * 
	 * @return void
	 */
	public static function add(string $label, string $url, bool $encode = true): void
	{
		self::$crumbs[] = static::createCrumb($encode ? Html::encode($label) : $label, $url);
	}

	/**
	 * Clear all breadcrumbs
	 * 
	 * @return void
	 */
	public static function clear(): void
	{
		self::$crumbs = [];
	}

	/**
	 * Set breadcrumbs home link
	 * 
	 * @param string $label
	 * @param string $url
	 * 
	 * @return array
	 */
	public static function getHomeCrumb(): array
	{
		return static::createCrumb(static::$homeLabel, Url::$home);
	}

	/**
	 * Get breadcrumbs except homelink
	 * 
	 * @return array
	 */
	public static function getCrumbs(): array
	{
		return static::$crumbs;
	}

	/**
	 * Get breadcrumbs with homelink
	 * 
	 * @return array
	 */
	public static function getAllCrumbs(): array
	{
		$crumbs = self::$crumbs;
		array_unshift($crumbs, static::getHomeCrumb());
		return $crumbs;
	}

	/**
	 * Set last breadcrumbs link active or not
	 * 
	 * @param bool $status
	 * 
	 * @return void
	 */
	public static function activeLastLink(bool $status): void
	{
		self::$activeLastLink = $status;
	}

	private static function createCrumb(string $label, string $url): array
	{
		$crumb = ['label' => $label, 'url' => $url];
		return $crumb;
	}
}