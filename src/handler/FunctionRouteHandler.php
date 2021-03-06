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

namespace buffalokiwi\telephonist\handler;

use buffalokiwi\telephonist\RouteConfigurationException;
use Closure;


/**
 * A handler that accepts a closure on calls to execute 
 */
class FunctionRouteHandler implements IRouteHandler
{
  private bool $addContextToNamedRoutes;
  
  /**
   * @param bool $addContextToNamedRoutes Set to true to set the argument 'context' equal to $context
   */
  public function __construct( bool $addContextToNamedRoutes = false )
  {
    $this->addContextToNamedRoutes = $addContextToNamedRoutes;
  }
  
  
  /**
   * Execute some endpoint handler.  This will execute either resource or the identifier at resource.
   * @param string|object $resource A function to call
   * @param string $identifier This is unused 
   * @param array $args Arguments
   * @param array $context Unused 
   * @return mixed
   */
  public function execute( string|object $resource, string $identifier = '', array $args = [], array $context = [] ) : mixed
  {
    if ( !( $resource instanceof Closure ))
      throw new RouteConfigurationException( 'Route resource must be a clsoure when using ' . static::class );
    
    
    if ( is_int( array_key_first( $args )))
      $args[] = $context;
    else if ( $this->addContextToNamedRoutes )
      $args['context'] = $context;
    
    return $resource( ...$args );
  }
}

