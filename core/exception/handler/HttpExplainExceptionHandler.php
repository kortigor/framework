<?php

declare(strict_types=1);

namespace core\exception\handler;

use Psr\Http\Message\ServerRequestInterface;
use core\exception\HttpExplainException;
use core\web\ServerRequest;
use core\helpers\Html;
use Throwable;

class HttpExplainExceptionHandler extends ExceptionHandlerAbstract
{
	/**
	 * @var string Template to render exception
	 */
	public string $template = '/core/views/error';

	public function handle(ServerRequestInterface $request, Throwable $e, string $format)
	{
		/** @var HttpExplainException $e */
		/** @var ServerRequest $request */

		$this->httpStatus = $e->getHttpStatus();
		$this->messages[] = $e->getMessage();
		return $this->render($e, $format, $request->isAjax());
	}

	protected function renderHtml(Throwable $e, bool $isAjax): string
	{
		if ($isAjax) {
			return $this->renderHtmlAjax($e);
		}

		$this->getView()->assign('message', $this->messageAsHtml($this->messages));
		$this->getView()->assign('trace', '');
		return $this->getView()->render($this->template);
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
}