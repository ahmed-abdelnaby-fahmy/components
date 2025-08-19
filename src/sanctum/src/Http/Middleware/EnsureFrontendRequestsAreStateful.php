<?php

declare(strict_types=1);

namespace Hypervel\Sanctum\Http\Middleware;

use Hyperf\Collection\Collection;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface as HttpResponse;
use Hypervel\Dispatcher\Pipeline;
use Hypervel\Support\Str;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class EnsureFrontendRequestsAreStateful implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected RequestInterface $request,
        protected HttpResponse $response,
        protected Pipeline $pipeline
    ) {
        $this->configureSecureCookieSessions();
    }

    /**
     * Process the incoming request.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (static::fromFrontend($this->request)) {
            // Mark request as stateful
            $request = $request->withAttribute('sanctum', true);

            return $this->pipeline
                ->send($request)
                ->through(config('sanctum.middleware'))
                ->then(function ($request) use ($handler) {
                    return $handler->handle($request);
                });
        }

        return $handler->handle($request);
    }

    /**
     * Determine if the given request is from the first-party application frontend.
     */
    public static function fromFrontend(RequestInterface $request): bool
    {
        $referer = $request->header('referer');
        $origin = $request->header('origin');

        $domain = $referer ?: $origin;

        if (is_null($domain)) {
            return false;
        }

        $domain = Str::replaceFirst('https://', '', $domain);
        $domain = Str::replaceFirst('http://', '', $domain);
        $domain = Str::endsWith($domain, '/') ? $domain : "{$domain}/";

        $stateful = array_filter(config('sanctum.stateful', []));

        return Str::is(Collection::make($stateful)->map(function ($uri) {
            return trim($uri) . '/*';
        })->all(), $domain);
    }

    /**
     * Configure secure cookie sessions.
     */
    protected function configureSecureCookieSessions(): void
    {
        config([
            'session.http_only' => true,
            'session.same_site' => 'lax',
        ]);
    }
}
