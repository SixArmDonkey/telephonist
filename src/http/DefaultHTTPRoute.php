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

use buffalokiwi\telephonist\handler\IRouteHandler;
use Closure;
use InvalidArgumentException;


class DefaultHTTPRoute extends HTTPRoute implements IHTTPRoute
{
  private Closure $endpoint;

  
  
  /**
   * 
   * @param IRouteHandler $routeHandler The handler to use on execute() 
   * @param string $path Path pattern 
   * @param Closure $endpoint
   * @param array<string> $options optional options 
   * @param array<string,mixed> $context context array 
   * @throws InvalidArgumentException If path is empty 
   */
  public function __construct( IRouteHandler $routeHandler, string $path, Closure $endpoint, 
    array $options = [], array $context = [] )
  {
    parent::__construct( $routeHandler, $path, $options, $context );
    
    $this->endpoint = $endpoint;
  }
  
  
  /**
   * Get the resource to execute 
   * @return class-string|object
   */
  protected function getResource() : string|object
  {
    return $this->endpoint;
  }
}
