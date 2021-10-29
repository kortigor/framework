<?php

declare(strict_types=1);

namespace core\base;

use Throwable;
use core\exception\ErrorException;

class ExceptionsHandler
{
	public static function register()
	{
		set_exception_handler(function (Throwable $e) {
			// clear all headers and buffers
			if (!headers_sent()) {
				header_remove();
			}

			while (ob_get_level()) {
				if (!ob_end_clean()) {
					ob_clean();
				}
			}

			$httpStatusCode = 500;
			$getName = [$e, 'getName'];
			$name = is_callable($getName) ? $getName() : get_class_short($e);

			header('HTTP/1.1 ' . $httpStatusCode);
			header('Status: ' . $httpStatusCode);
			header('Content-Type: text/html; charset=UTF-8');
			printr($name);
			printr($e->getMessage());
			printr('In ' . $e->getFile() . ' on line ' . $e->getLine());
			printr("Call stack:\n" . $e->getTraceAsString());
			printr('Exception thrown: ' . get_class($e));
		});

		set_error_handler(function ($code, $message, $file, $line) {
			if (error_reporting() && $code && SYS_DEBUG) {
				throw new ErrorException($message, $code, $code, $file, $line);
			}

			return false;
		});
	}
}