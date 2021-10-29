<?php

declare(strict_types=1);

namespace core\exception\handler;

use Psr\Http\Message\ServerRequestInterface;
use core\exception\HttpException;
use core\web\ServerRequest;
use core\helpers\Html;
use Throwable;

class HttpExceptionHandler extends ExceptionHandlerAbstract
{
	/**
	 * @var string Template to render exception
	 */
	public string $template = '/core/views/error';

	/**
	 * @var string[]
	 */
	private array $traces = [];

	public function handle(ServerRequestInterface $request, Throwable $e, string $format)
	{
		/** @var HttpException $e */
		/** @var ServerRequest $request */

		$this->httpStatus = $e->getHttpStatus();
		$this->messages[] = t(c('main.httpErrors.' . $this->httpStatus) ?? 'Ошибка запроса');
		if ($request->get('debug') !== null || $request->isAjax()) {
			$this->convertException($e);
			if ($ep = $e->getPrevious()) {
				$this->convertException($ep);
			}
		}

		return $this->render($e, $format, $request->isAjax());
	}

	protected function renderHtml(Throwable $e, bool $isAjax): string
	{
		if ($isAjax) {
			return $this->renderHtmlAjax($e);
		}

		$this->getView()->assign('message', $this->messageAsHtml($this->messages));
		$this->getView()->assign('trace', $this->traceAsHtml($this->traces, '<p>Информация для отладки:</p>'));
		return $this->getView()->render($this->template);
	}

	private function convertException(Throwable $e): void
	{
		$class = get_class_short($e);
		$this->messages[] = sprintf('%s message: %s', $class, $e->getMessage());
		$this->traces[] = $e->getTraceAsString();
	}

	private function messageAsHtml(array $source, string $header = ''): string
	{
		$html = '';
		if (count($source) > 1) {
			$html = Html::ol($source, ['class' => 'card card-body pl-5']);
		} elseif (!empty($source)) {
			$html = Html::tag('div', $source[0], ['class' => 'card card-body mb-3']);
		}

		return $html ? $header . $html : $html;
	}

	private function traceAsHtml(array $source, string $header = ''): string
	{
		$html = '';
		if (count($source) > 1) {
			$html = Html::ol($source, ['class' => 'pl-3']);
			$html = Html::tag('pre', $html, ['class' => 'alert alert-danger']);
		} elseif (!empty($source)) {
			$html = Html::tag('pre', $source[0], ['class' => 'alert alert-danger']);
		}

		return $html ? $header . $html : $html;
	}
}