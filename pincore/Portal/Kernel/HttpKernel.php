<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal\Kernel;

use Pinoox\Component\Kernel\Kernel;
use Pinoox\Component\Router\RouteCollection;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\App;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Event;
use Pinoox\Portal\FlowManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response as ObjectPortal1;

/**
 * @method static ObjectPortal1 handleSubRequest(\Symfony\Component\HttpFoundation\Request $request)
 * @method static ObjectPortal1 handle(\Symfony\Component\HttpFoundation\Request $request, int $type = 1, bool $catch = true)
 * @method static terminate(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Response $response)
 * @method static HttpKernel terminateWithException(\Throwable $exception, ?\Symfony\Component\HttpFoundation\Request $request = NULL)
 * @method static \Symfony\Component\HttpFoundation\RequestStack ___requestStack()
 * @method static \Symfony\Component\Routing\RequestContext ___context()
 * @method static \Symfony\Component\Routing\Matcher\UrlMatcher ___matcher()
 * @method static \Symfony\Component\Routing\Generator\UrlGenerator ___urlGenerator()
 * @method static \Pinoox\Component\Kernel\Kernel ___()
 *
 * @see \Pinoox\Component\Kernel\Kernel
 */
class HttpKernel extends Portal
{
    public static function __register(): void
    {
        self::setParams();
        self::__bind(RequestStack::class, 'request_stack');

        self::__bind(Kernel::class)
            ->setArguments([
                Event::__ref(),
                Resolver::__ref('controller'),
                self::__ref('request_stack'),
                Resolver::__ref('argument'),
            ])->addMethodCall('setFlowManager', [
                FlowManager::__ref(),
            ]);

        self::setRouteDefault();
    }

    public static function set(string $package, string $path): void
    {
        $router = AppEngine::router($package, $path);
        self::setRouts($router->getCollection()->routes);
    }

    public static function setRouts(RouteCollection $routes): void
    {
        self::__bind($routes, 'routes');
    }

    public static function setRouteDefault(): void
    {
        self::setRouts(App::routeCollection());
    }

    private static function setParams(): void
    {
        self::__param('charset', 'UTF-8');
    }


    /**
     * Get the registered name of the component.
     * @return string
     */
    public static function __name(): string
    {
        return 'kernel';
    }


    /**
     * Get exclude method names .
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }


    /**
     * Get method names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [
            'terminateWithException'
        ];
    }
}
