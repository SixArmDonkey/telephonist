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
use InvalidArgumentException;
use function ctype_digit;


class ClassHTTPRoute extends HTTPRoute implements IHTTPRoute
{
  private string $class;
  private string $method;
  
  
  /**
   * @param IRouteHandler $routeHandler The handler to use on execute() 
   * @param string $path Path pattern 
   * @param string $class Class or file name 
   * @param string $method method when class is a class 
   * @param array $options optional options 
   * @param array $context context array 
   * @throws InvalidArgumentException If path or class is empty 
   */
  public function __construct( IRouteHandler $routeHandler, string $path, string $class, 
          string $method = '', array $options = [], array $context = [] )
  {
    parent::__construct( $routeHandler, $path, $options, $context );
    
    if ( empty( $class ))
      throw new InvalidArgumentException( 'class for path "' . $path . '" must not be empty' );
    
    $this->class = $class;
    $this->method = $method;
  }
  
  /**
   * Get the class 
   */
  protected function getResource() : mixed
  {
    return $this->class;
  }
  
  
  /**
   * Get the method 
   * @return string
   */
  protected function getIdentifier() : string
  {
    return $this->method;
  }
}
