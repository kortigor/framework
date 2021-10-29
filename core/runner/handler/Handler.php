<?php

declare(strict_types=1);

namespace core\runner\handler;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Simple middleware runner.
 * 
 * Example:
 * ```
 * use core\runner\handler\Handler;
 * use core\runner\handler\PathResolver;
 * 
 * $manager = new Handler(new PathResolver);
 * 
 * $manager
 *  ->use($logMiddleware, '/admin/.*') // invoke only path /admins/.*
 *  ->use($logMiddleware, '!/admin/.*') // invoke all paths except /admins/.*
 * 	->use(new RouterMiddleware);
 * 
 * $response = $manager->handle($request);
 * ```
 */
class Handler implements RequestHandlerInterface
{
    /**
     * @var array
     */
    protected array $next = [];

    /**
     * @var array
     */
    protected array $id = [];

    /**
     * Constructor.
     *
     * @param PathResolver $pathResolver
     */
    public function __construct(protected PathResolver $pathResolver)
    {
    }

    /**
     * @param int $index
     * 
     * @return array|null
     */
    public function get(int $index = 0): ?array
    {
        return $this->next[$index] ?? null;
    }

    /**
     * Get middleware component by id.
     * 
     * @param string $id Component id, same as class name.
     * 
     * @return MiddlewareInterface
     * @throws InvalidArgumentException If component with given id was not found.
     */
    public function getById(string $id): MiddlewareInterface
    {
        if (!isset($this->id[$id])) {
            throw new InvalidArgumentException("Middleware with id {$id} not found");
        }
        $index = $this->id[$id];
        return $this->next[$index][0];
    }

    /**
     * @param PathResolver $pathResolver
     * 
     * @return self
     */
    public function setPathResolver(PathResolver $pathResolver): self
    {
        $this->pathResolver = $pathResolver;
        return $this;
    }

    /**
     * @param MiddlewareInterface $middleware
     * @param string|null $path
     *
     * @return self
     */
    public function use(MiddlewareInterface $middleware, string $path = null): self
    {
        $id = get_class_short($middleware);
        if (isset($this->id[$id])) {
            throw new InvalidArgumentException("Middleware with id {$id} already in use");
        }

        $this->next[] = [$middleware, $path];
        $index = count($this->next) - 1;
        $this->id[$id] = $index;

        return $this;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $runner = $this->createRunner();

        return $runner->handle($request);
    }

    /**
     * @return Runner
     */
    protected function createRunner(): Runner
    {
        $runner = new Runner($this);
        $runner->setPathResolver($this->pathResolver);

        return $runner;
    }
}