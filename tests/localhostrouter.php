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

use buffalokiwi\telephonist\DefaultRouteConfig;
use buffalokiwi\telephonist\http\ArrayRouteFactory;
use buffalokiwi\telephonist\http\BasicRouter;
use buffalokiwi\telephonist\http\DefaultHTTPRouteOptions;
use buffalokiwi\telephonist\http\DefaultHTTPRouter;
use buffalokiwi\telephonist\http\DefaultHTTPRouteRequest;
use buffalokiwi\telephonist\http\DefaultRouteFactory;
use buffalokiwi\telephonist\http\HTTPRouteFactoryGroup;
use buffalokiwi\telephonist\http\MethodRouteOption;
use buffalokiwi\telephonist\http\XMLHTTPRequestRouteOption;
use buffalokiwi\telephonist\RouteNotFoundException;




$withMethod = new DefaultHTTPRouter(
  (new DefaultRouteFactory())
    ->add( 
      '/',  //..The path pattern
      static function( array $context ) { //..The route 
        //..The route content
        return 'This is the home page for context ' . $context['context'];
      }, 
      ['GET'], //..Options/flags
      ['context' => 'foo'] ), //..Anything you want in an array 
  new DefaultHTTPRouteOptions( 
    new MethodRouteOption( MethodRouteOption::GET ))
);
  


class LocalRouterTest
{
  public const ROUTE_CONFIG = [
    'test' => [
       '(?<i>\d+)' => [LocalRouterTest::class, 'helloRouterArg', ['GET'], []],
       '' => [LocalRouterTest::class, 'helloRouter', ['GET'], []]
    ]
  ];

  public static function helloRouter() : string
  {
    return 'Hello Router!';
  }
  
  
  public static function helloRouterArg( int $i, array $context ) : string
  {
    var_dump( $context );
    return 'Hello Router ' . (string)$i . '!';
  }
}


$router = new DefaultHTTPRouter(
  new HTTPRouteFactoryGroup(
    new ArrayRouteFactory(
      new DefaultRouteConfig( fn() => LocalRouterTest::ROUTE_CONFIG ), 
      ArrayRouteFactory::createDefaultRouteFactory( true )),
    (new DefaultRouteFactory( DefaultRouteFactory::createDefaultRouteFactory( true )))
    ->add( '/', static function( array $context ) {
      return 'Home page';
    })
    ->add( 'test2', static function( array $context ) {
      return 'Hello Router 2!';
    })
    ->add( 'test2/(?<int>\d+[a-z])', static function( int|string $int, array $context ) {
      return 'Found ' . (string)$int . ' with context ' . $context['context'];
    }, ['GET'], ['context' => 'foo'] )
    ->add( '/blog(/\d+)?(/\d+)?(/\d+)?(/[a-z0-9_-]+)?', static function( ?int $year = null, ?int $month = null, ?int $day = null, ?string $slug = null, array $context = []) {
        if (!$year) { echo 'Blog overview'; return; }
        if (!$month) { echo 'Blog year overview'; return; }
        if (!$day) { echo 'Blog month overview'; return; }
        if (!$slug) { echo 'Blog day overview'; return; }
        echo 'Blogpost ' . htmlentities($slug) . ' detail';
    })
  ),
  new DefaultHTTPRouteOptions(
    new MethodRouteOption( MethodRouteOption::GET ),
    new XMLHTTPRequestRouteOption()
));

    
    


try {
  echo ( new BasicRouter([
    '(?<id>\d+)' => fn( int $id ) => 'Found digit ' . $id,
  ]))->route( new DefaultHTTPRouteRequest( $_SERVER ));
} catch( RouteNotFoundException $e ) {
  http_response_code( $e->getCode());  
}    
    
    
    

/*
try {
  echo $router->route( new DefaultHTTPRouteRequest( $_SERVER ));
} catch( RouteNotFoundException $e ) {
  http_response_code( $e->getCode());  
  echo 'Not found';
}
*/