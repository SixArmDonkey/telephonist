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


/**
 * The base implementation of an http route, using regular expressions to match against
 * the client request uri.
 * 
 * Note: The route handler must know how to handle whatever type of route this represents
 */
abstract class HTTPRoute implements IHTTPRoute
{
  /**
   * The context array key containing the resolved endpoint arguments
   */
  public const ARGS_CAPTURED = 'args_captured';
  
  /**
   * Route executor 
   * @var IRouteHandler
   */
  private IRouteHandler $routeHandler;
    
  /**
   * A regular expression used to match this route 
   * @var string
   */
  private string $pattern;  
  
  /**
   * A list of codes that will match 
   * instances of IHTTPRouteOption available in the router.
   * When the router contains a route option with a matching code from this list,
   * it is used as part of route validation.  If the code attached to the
   * route does not exist in the router, then it is ignored.
   * 
   * @var array<string>
   */
  private array $options;
  
  /**
   * A generic array containing anything
   * This is used to pass data around
   * @var array<string,mixed>
   */
  private array $context;
  
  /**
   * Get the resource to execute 
   * @return class-string|object
   */
  protected abstract function getResource() : string|object;

  
  /**
   * @param IRouteHandler $routeHandler When the route matches some uri, the execute() method must be called.
   * Execute() will invoke IRouteHandler::execute() and return the result.
   * 
   * @param string $path Path pattern 
   * 
   * 1) The supplied pattern uses tilde (~) as delimiter
   * 2) Any ~ within the pattern are converted to / 
   * 3) The pattern is wrapped with ^ and $. ie: ~^pattern$~
   * 4) Any capture groups are considered to be endpoint method arguments and must have matching types
   * 
   * @param array<string> $options optional options A list of option flags/codes/whatever. The values in this list 
   * must match the result of IHTTPRouteOption::getCommand()
   * 
   * @param array<string,mixed> $context context array Super fun time random array for things!! 
   * 
   * Put whatever you want in here and it gets passed around.  Anywhere you see $context, it is this data.
   * 
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
   * @return array<string,mixed>
   */
  public final function getContext() : array
  {
    return $this->context;
  }
  
  
  /**
   * Route options   
   * @return array<string>
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

  
  /**
   * Override this method to return a string representing the method to invoke on the supplied resource
   * @return string method name or an empty string if there is no method to invoke. 
   * Note: The route handler must know how to handle whatever types of route this is.
   */
  protected function getIdentifier() : string
  {
    return '';
  }
  
  
  /**
   * Add arguments to the argument array based on stuff extracted from the route path
   * @param array<string> $matches preg_match() matches 
   * @param array &$args Argument output
   * @return void
   */
  private function setArguments( array $matches, array &$args ) : void
  {
    if ( isset( $matches[0] ))
      unset( $matches[0] );
    
    if ( !isset( $this->context[self::ARGS_CAPTURED] ) || !is_array( $this->context[self::ARGS_CAPTURED] ))
      $this->context[self::ARGS_CAPTURED] = [];
    
    $captured = $this->context[self::ARGS_CAPTURED];
    
    
    foreach( array_values( $matches ) as $k => $v )
    {
      if ( isset( $captured[$k] ) && !empty( $captured[$k] ) && is_string( $captured[$k] ))
      {
        /** @psalm-suppress MixedAssignment This is handled by castValue and is meant to be mixed **/
        $args[$captured[$k]] = $this->castValue( $v );
      }
      else
      {
        /** @psalm-suppress MixedAssignment This is handled by castValue and is meant to be mixed **/
        $args[$k] = $this->castValue( $v );
      }
    }
  }  
  
  
  /**
   * It's dumb, but this will cast $value to int or float if it contains an int or float.
   * 
   * This will return int|float|mixed 
   * 
   * @param string $value Value to cast
   * @return mixed
   */
  private function castValue( string $value ) : mixed 
  {
    if ( empty( $value ))
      return $value;
    else if ( ctype_digit( $value ))
      return (int)$value;
    else if ( is_numeric( $value ))
      return (float)$value;
    
    return $value;    
  }
}
