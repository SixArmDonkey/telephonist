# Telephonist



```php

use buffalokiwi\telephonist\DefaultRouteConfig;
use buffalokiwi\telephonist\http\DefaultHTTPRouteOptions;
use buffalokiwi\telephonist\http\DefaultHTTPRouter;
use buffalokiwi\telephonist\http\DefaultHTTPRouteRequest;
use buffalokiwi\telephonist\http\ArrayRouteFactory;
use buffalokiwi\telephonist\http\DefaultRouteFactory;
use buffalokiwi\telephonist\http\MethodRouteOption;
use buffalokiwi\telephonist\http\XMLHTTPRequestRouteOption;
use buffalokiwi\telephonist\RouteNotFoundException;
use buffalokiwi\telephonist\http\HTTPRouteFactoryGroup;

class LocalRouterTest
{
  public const ROUTE_CONFIG = [
    'test' => [LocalRouterTest::class, 'helloRouter', ['GET'], []],
    'test/(\d+)' => [LocalRouterTest::class, 'helloRouterArg', ['GET'], []]
  ];

  public static function helloRouter() : string
  {
    return 'Hello Router!';
  }
  
  
  public static function helloRouterArg( int $i ) : string
  {
    return 'Hello Router ' . (string)$i . '!';
  }
}

$router = new DefaultHTTPRouter(
  new HTTPRouteFactoryGroup(
    new ArrayRouteFactory(
      new DefaultRouteConfig( fn() => LocalRouterTest::ROUTE_CONFIG )),
    (new DefaultRouteFactory())
    ->add( 'test2', function() {
      return 'Hello Router 2!';
    })
    ->add( 'test2/(\d+)', function( int $int, array $context ) {
      return 'Found ' . (string)$int;
    }, ['GET'], ['context' => 'foo'] )
  ),
  new DefaultHTTPRouteOptions(
    new MethodRouteOption(),
    new XMLHTTPRequestRouteOption()
));


try {
  echo $router->route( new DefaultHTTPRouteRequest( $_SERVER ));
} catch( RouteNotFoundException $e ) {
  http_response_code( $e->getCode());  
  echo 'Not found';
}
```
