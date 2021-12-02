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


class FunctionalHandler implements IRouteHandler
{
  /**
   * Execute some endpoint handler.  This will execute either resource or the identifier at resource.
   * @param mixed $resource Class name, file name, etc 
   * @param string $identifier Optional. When resource is class, this would be the method name
   * @param array $args Arguments
   * @param array $context Context 
   * @return mixed
   */  
  public function execute( mixed $resource, string $identifier = '', array $args = [], array $context = [] ) : mixed
  {
    if ( !( $resource instanceof Closure ))
      throw new RouteConfigurationException( 'Route resource must be a clsoure when using ' . static::class );
    
    $args[] = $context;
    
    return $resource( ...$args );
  }
}

