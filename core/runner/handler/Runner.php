<?php

declare(strict_types=1);

namespace core\runner\handler;

use OutOfRangeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Runner implements RequestHandlerInterface
{
    /**
     * @var PathResolver
     */
    protected PathResolver $pathResolver;

    /**
     * @var int
     */
    protected int $index = 0;

    public function __construct(protected Handler $manager)
    {
    }

    /**
     * @inheritdoc
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        [$next, $path] = $this->manager->get($this->index);

        if ($next === null) {
            throw new OutOfRangeException('Handler not found');
        }

        $this->rewindBy(1);
        $response = $this->callNext($path, $request, $next);
        $this->rewindBy(-1);

        return $response;
    }

    protected function callNext(?string $path, ServerRequestInterface $request, MiddlewareInterface $next)
    {
        if ($path === null) {
            return $next->process($request, $this);
        }

        return $this->pathResolver->isMatch($path, $request)
            ? $next->process($request, $this)
            : $this->handle($request);
    }

    public function setPathResolver(PathResolver $resolver): void
    {
        $this->pathResolver = $resolver;
    }

    public function getHandler(): Handler
    {
        return $this->manager;
    }

    protected function rewindBy(int $val = 1): void
    {
        $this->index += $val;
    }
}