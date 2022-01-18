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

namespace buffalokiwi\telephonist\http;

use buffalokiwi\telephonist\RouteConfigurationException;
use Closure;


/**
 * A very basic router.
 * 
 * new BasicRouter([
 
 * ]);
 * 
 * 
 * 
 */
class BasicRouter extends DefaultHTTPRouter
{
  /**
   * 
   * @param array<string,\Closure> $routes A list of route functions
   * The key is the path pattern (regex).  Capture groups are passed as arguments to the route endponit function 
   * 
   * A route function can be:
   * 
   * 1) function() : mixed
   * 2) function( capture,group,values ) : mixed
   * 3) function( capture,group,values, array $context ) : mixed 
   */
  public function __construct( array $routes )
  {
    parent::__construct( $this->createRouteFactory( $routes ), new DefaultHTTPRouteOptions());
  }
  
  
  /**
   * @param array<string,\Closure> $routes Routes
   * @return DefaultRouteFactory The route factory 
   * @throws RouteConfigurationException
   */
  private function createRouteFactory( array $routes ) : DefaultRouteFactory
  {
    $factory = new DefaultRouteFactory();
    
    foreach( $routes as $path => $route )
    {
      if ( !( $route instanceof Closure ))
        throw new RouteConfigurationException( 'Route (array values) must be instances of \Closure' );
      
      $factory->add( $path, $route );
    }
    
    return $factory;
  }
}

