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
   * @param IRouteHandler $routeHandler The handler to use on execute() 
   * @param string $path Path pattern 
   * @param string $class Class or file name 
   * @param string $method method when class is a class 
   * @param array $options optional options 
   * @param array $context context array 
   * @throws InvalidArgumentException If path or class is empty 
   */
  public function __construct( IRouteHandler $routeHandler, string $path, Closure $endpoint, 
    array $options = [], array $context = [] )
  {
    parent::__construct( $routeHandler, $path, $options, $context );
    
    $this->endpoint = $endpoint;
  }
  
  
  /**
   * Get the resource to execute 
   */
  protected function getResource() : mixed
  {
    return $this->endpoint;
  }
}
