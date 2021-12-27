<?php
/**
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.txt', which is part of this source code package.
 *
 * Copyright (c) 2021 John Quinn <johnquinn3@gmail.com>
 * 
 * @author John Quinn
 */

declare( strict_types=1 );

namespace buffalokiwi\telephonist\tests;
require_once( __DIR__ . '/bootstrap.php' );

use buffalokiwi\telephonist\FunctionalRouteConfig;
use buffalokiwi\telephonist\http\DefaultHTTPRouteOptions;
use buffalokiwi\telephonist\http\DefaultHTTPRouter;
use buffalokiwi\telephonist\http\DefaultHTTPRouteRequest;
use buffalokiwi\telephonist\http\FunctionalNestedArrayRouteFactory;
use buffalokiwi\telephonist\http\FunctionalRouteFactory;
use buffalokiwi\telephonist\http\MethodRouteOption;
use buffalokiwi\telephonist\http\XMLHTTPRequestRouteOption;
use buffalokiwi\telephonist\RouteNotFoundException;
use buffalokiwi\teleponist\http\HTTPRouteFactoryGroup;


class LocalRouterTest
{
  public const ROUTE_CONFIG = [
    'test' => [
       '(\d+)' => [LocalRouterTest::class, 'helloRouterArg', ['GET'], []],
       '' => [LocalRouterTest::class, 'helloRouter', ['GET'], []]
    ]
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
    new FunctionalNestedArrayRouteFactory(
      new FunctionalRouteConfig( fn() => LocalRouterTest::ROUTE_CONFIG )),
    (new FunctionalRouteFactory())
    ->add( 'test2', function() {
      return 'Hello Router 2!';
    })
    ->add( 'test2/(\d+[a-z])', function( int|string $int, array $context ) {
      return 'Found ' . (string)$int . ' with context ' . $context['context'];
    }, ['GET'], ['context' => 'foo'] )
  ),
  new DefaultHTTPRouteOptions(
    new MethodRouteOption( MethodRouteOption::GET ),
    new XMLHTTPRequestRouteOption()
));


try {
  echo $router->route( new DefaultHTTPRouteRequest( $_SERVER ));
} catch( RouteNotFoundException $e ) {
  http_response_code( $e->getCode());  
  echo 'Not found';
}
