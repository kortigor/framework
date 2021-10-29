<?php

declare(strict_types=1);

namespace core\routing;

/**
 * Base routing rules
 */
class BaseRules
{
	/**
	 * @var array Routing rules to place at the top position. Only by performance reason.
	 */
	public static array $top = [
		['site-root', '/', '/'],
	];

	/**
	 * @var array Standard routing rules.
	 * Placed to down position, so custom rules can match before standard rules.
	 */
	public static array $standard = [
		['controller', '/{controller:str}', '{controller}/'],
		['controller-id-opt', '/{controller:str}/{id:num}{seostuff:any}/{opt1:any:?}', '{controller}/'],
		['controller-action', '/{controller:str}/{action:str}', '{controller}/{action}'],
		['controller-action-2-opts', '/{controller:str}/{action:str}/{opt1:any}/{opt2:any:?}', '{controller}/{action}'],
		['controller-action-uuid', '/{controller:str}/{action:str}/{id:uuid}{seostuff:any:?}', '{controller}/{action}'],
		['controller-action-id', '/{controller:str}/{action:str}/{id:num}{seostuff:any:?}', '{controller}/{action}'],
	];

	public static function collection(): array
	{
		return [
			Rule::PRIORITY_TOP => static::$top,
			Rule::PRIORITY_FINAL => static::$standard,
		];
	}
}