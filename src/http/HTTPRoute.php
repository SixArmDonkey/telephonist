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



abstract class HTTPRoute implements IHTTPRoute
{
  public const ARGS_CAPTURED = 'args_captured';
  
  private IRouteHandler $routeHandler;
  private string $pattern;
  private array $options;
  private array $context;
  
  
  /**
   * Get the resource to execute 
   */
  protected abstract function getResource() : mixed;

  
  /**
   * @param IRouteHandler $routeHandler The handler to use on execute() 
   * @param string $path Path pattern 
   * @param array $options optional options 
   * @param array $context context array 
   * @throws InvalidArgumentException If path is empty 
   */
  public function __construct( IRouteHandler $routeHandler, string $path, array $options = [], array $context = [] )
  {
    if ( empty( $path ))
      throw new InvalidArgumentException( 'Path must not be empty' );
    
    if ( substr( $path, 0, 1 ) != '/' )
      $path = '/' . $path;
    
    $this->routeHandler = $routeHandler;
    $this->pattern = '~^' . str_replace( '~', '/', $path ) . '$~';
    $this->options = $options;
    $this->context = $context;
  }  
  
  
  /**
   * Get the pattern for this route
   * @return string
   */
  public final function getPattern() : string
  {
    return $this->pattern;
  }
  
  
  /**
   * Route context
   * @return array
   */
  public final function getContext() : array
  {
    return $this->context;
  }
  
  
  /**
   * Route options   
   * @return array
   */
  public final function getOptions() : array
  {
    return $this->options;
  }
  
  
  /**
   * Test if some uri matches a regular expression.
   * 
   * @param IHTTPRouteRequest $uri
   * @param array &$matchedValues Matched argument values.  
   * @return bool If the route matches 
   */
  public final function matches( IHTTPRouteRequest $request, array &$matchedValues ) : bool
  {
    $matchedValues = [];
    $uri = $request->getURI();
    $matches = [];
    
    if ( substr( $uri, -1 ) == '/' )
      $uri = substr( $uri, 0, -1 );
    
    if ( preg_match( $this->pattern, ( empty( $uri )) ? '/' : $uri, $matches ))
    {
      $this->setArguments( $matches, $matchedValues );
      return true;
    }
    
    return false;
  }
  
  
  /**
   * Execute the route 
   * @param array $matchedValues The arguments passed to the endpoint.  If the keys are strings, then 
   * we must match the argument name to the name of some method or function argument via reflection.  If the keys are
   * numeric, then the arguments are simply passed to the method or function as is.  
   * @return mixed
   */
  public final function execute( array $matchedValues ) : mixed
  {
    return $this->routeHandler->execute( $this->getResource(), $this->getIdentifier(), $matchedValues, $this->getContext());
  }    

  
  protected function getIdentifier() : string
  {
    return '';
  }
  
  
  /**
   * Add arguments to the argument array based on stuff extracted from the route path
   * @param array $matches preg_match() matches 
   * @param array &$args Argument output
   * @return void
   */
  private function setArguments( array $matches, array &$args ) : void
  {
    if ( isset( $matches[0] ))
      unset( $matches[0] );
    
    $captured = ( isset( $this->context[self::ARGS_CAPTURED] )) ? $this->context[self::ARGS_CAPTURED] : [];
    
    if ( !is_array( $captured )) //..ehhhhh this should be a warning or something, you lazy bastard
      $captured = [];
    
    foreach( array_values( $matches ) as $k => $v )
    {
      if ( isset( $captured[$k] ) && !empty( $captured[$k] ) && is_string( $captured[$k] ))
        $args[$captured[$k]] = $this->castValue( $v );
      else
        $args[$k] = $this->castValue( $v );
    }
  }  
  
  
  private function castValue( string $value ) : mixed 
  {
    if ( $value === null )
      return $value;
    
    if ( ctype_digit((string)$value ))
      return (int)$value;
    else if ( is_numeric( $value ))
      return (float)$value;
    
    return $value;    
  }
}

