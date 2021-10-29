<?php

declare(strict_types=1);

use core\di\Instance;

// Turn off all error reporting
// error_reporting(0);

// Turn on all error reporting
error_reporting(E_ALL);

// Session cookie settings
session_set_cookie_params([
	'SameSite' => 'Strict',
	'HttpOnly' => 'true',
]);

return [
	'appId' => 'default',
	'homeUrl' => '/',
	'loginURL' => '/login/',
	'rootPath' => normalizePath($_SERVER['DOCUMENT_ROOT']), // Normalization need to guarantee to remove trailing slash
	'logPath' => fsPath('/log'),
	'cookieLifeTime' => time() + 60 * 60 * 24 * 365,
	'timeZone' => 'Etc/GMT-7',
	'charset' => 'UTF-8',
	'language' => [
		'default' => 'ru',
		'supported' => ['ru', 'en'],
	],
	'remoteIp' => $_SERVER['REMOTE_ADDR'],
	'auth' => [
		'loginDuration' => 60 * 60 * 1,
		'rememberDuration' => 60 * 60 * 24 * 365,
		'identityCookieName' => '__user',
		'identityClass' => \customer\entities\Employee::class,
		'returnUrlCookieName' => '__returnUrl',
		'authTokenName' => 'authToken',
	],
	'httpErrors' => [
		403 => 'Доступ запрещен',
		404 => 'К сожалению страница не существует',
		500 => 'Ошибка сервера'
	],
	'reCaptcha' => [
		'siteKey' => '',
		'secretKey' => ''
	],
	'search' => [
		'minLengthToSuggest' => 3, // minimal symbols to enter to suggestions appears
		'suggestsLimit' => 10, // number of suggests limit
		'suggestionLength' => 80, // maximum symbols in suggestion
	],

	/**
	 * DI container configuration. For DI container config usage:
	 * @see \core\di\Configurator
	 * @see \core\base\Application::initContainer()
	 */
	'container' => [
		'components' => [
			'orm' => \core\orm\EloquentOrm::class,
			'handler' => \core\runner\handler\Handler::class,
			'mail' => [
				'@d' => \core\interfaces\MailerFacadeInterface::class,
				'@p' => [Instance::of('confMail')]
			],
			'i18n' => [
				'@d' => fn ($container, $params, $config) => \Laminas\I18n\Translator\Translator::factory(...$params),
				'@p' => [
					'options' => [
						'locale' => [
							'ru', // Default locale
						],
						'translation_files' => [
							[
								'type'     => \Laminas\I18n\Translator\Loader\PhpArray::class,
								'filename' => __DIR__ . '/../../languages/en.php',
								'locale'   => 'en',
								'text_domain' => 'app',
							],
						],
						'translation_file_patterns' => [
							[
								'type'     => \Laminas\I18n\Translator\Loader\PhpArray::class,
								'base_dir' => __DIR__ . '/../../languages',
								'pattern'  => '%s.php',
							],
						],
					]
				]
			],
			'formatter' => [
				'@d' => \core\helpers\Formatter::class,
				'@p' => [
					[
						'dateFormat' => 'dd.MM.yyyy',
						'decimalSeparator' => ',',
						'thousandSeparator' => ' ',
						'currencyCode' => 'RUB',
						'sizeFormatBase' => 1000,
						'language' => 'ru',
						'locale' => 'ru',
					]
				]
			],
			'user' => \core\web\User::class,
		],

		'singletons' => [
			'emitter' => \core\runner\emitter\EmitterInterface::class,
			'confMail' => [
				'@d' => \core\mail\Config::class,
				'@p' => [
					'host' => '',
					'port' => 465,
					'encryption' => '',
					'login' => '',
					'password' => '!vf',
					'charset' => 'utf-8',
					'from' => fn () => c('site.site_title') ?: 'Сайт компании "Компания"',
					'email' => fn () => c('site.site_email') ?: 'info@site.ru',
					'layout' => '/core/views/layouts/blank'
				]
			],
			\core\routing\Router::class => [
				'@d' => [
					'cacheDir' => DATA_ROOT_PHP . '/cache/routing',
					'useCache' => true
				],
			],
			\core\orm\Config::class => [
				'driver' => 'mysql',
				'host' => 'localhost',
				'database' => '',
				'prefix' => '',
				'username' => '',
				'password' => '',
				'charset' => 'utf8',
				'collation' => 'utf8_general_ci',
			],
			// Just for convenience to get \core\web\User dependencies from container
			\core\web\User::class => ['@class' => Instance::of('user')],
		],

		'dependencies' => [
			\core\interfaces\MailerFacadeInterface::class => \core\mail\MailerSwift::class,
			\core\interfaces\MailMessageFacadeInterface::class => \core\mail\MailMessageSwift::class,
			\core\runner\emitter\EmitterInterface::class => \core\runner\emitter\Emitter::class,
			\core\routing\RuleProviderInterface::class => \core\routing\RuleProvider::class,
		],
	],
];