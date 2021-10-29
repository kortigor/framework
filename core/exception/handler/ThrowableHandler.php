<?php

declare(strict_types=1);

namespace core\exception\handler;

use Psr\Http\Message\ServerRequestInterface;
use core\web\ServerRequest;
use Throwable;

class ThrowableHandler extends ExceptionHandlerAbstract
{
	public function handle(ServerRequestInterface $request, Throwable $e, string $format)
	{
		/** @var ServerRequest $request */
		$this->messages[] = $e->getMessage();
		return $this->render($e, $format, $request->isAjax());
	}

	protected function renderHtml(Throwable $e, bool $isAjax): string
	{
		if ($isAjax) {
			return $this->renderHtmlAjax($e);
		}

		$getName = [$e, 'getName'];
		$header = is_callable($getName) ? $getName() : get_class_short($e);

		$trace = $this->getOption('details') ? $e->getTraceAsString() : null;
		$view = $this->getView();
		$view->assign('class', get_class($e));
		$view->assign('header', $header);
		$view->assign('message', $e->getMessage());
		$view->assign('file', $e->getFile());
		$view->assign('line', $e->getLine());
		$view->assign('trace', $trace);

		$ep = $e;
		while ($ep = $ep->getPrevious()) {
			$previous[] = [
				'class' => get_class($ep),
				'file' => $ep->getFile(),
				'line' => $ep->getLine(),
			];
		}
		$view->assign('previous', $previous ?? []);

		return $view->render($this->template);
	}
}