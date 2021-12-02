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

namespace buffalokiwi\teleponist\http;

use buffalokiwi\telephonist\http\IHTTPRoute;
use buffalokiwi\telephonist\http\IHTTPRouteFactory;
use buffalokiwi\telephonist\http\IHTTPRouteRequest;
use InvalidArgumentException;


class HTTPRouteFactoryGroup implements IHTTPRouteFactory
{
  private array $factoryList;
  
  public function __construct( IHTTPRouteFactory ...$factoryList )
  {
    if ( empty( $factoryList ))
      throw new InvalidArgumentException( 'factoryList must not be null' );
    
    $this->factoryList = $factoryList;
  }
  
  
  /**
   * Retrieve a list of possible route patterns and configurations based on the supplied uri 
   * @param IHTTPRouteRequest $request 
   * @return IHTTPRoute[] Possible routes 
   */
  public function getPossibleRoutes( IHTTPRouteRequest $request ) : array
  {
    $out = [];
    
    foreach( $this->factoryList as $factory )
    {
      foreach( $factory->getPossibleRoutes( $request ) as $route )
      {
        $out[] = $route;
      }
    }
    
    return $out;
  }  
}
