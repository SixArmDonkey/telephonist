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

use buffalokiwi\telephonist\handler\FunctionRouteHandler;
use buffalokiwi\telephonist\RouteConfigurationException;
use Closure;
use Generator;
use InvalidArgumentException;



class DefaultRouteFactory implements IHTTPRouteFactory
{
  /**
   * Path delimiter
   */
  private const PATH_DELIM = '/';

  
  /**
   * Closure for creating instances of IHTTPRoute.
   * fn( string $path, \Closure $endpoint, array $options, array $context ) : IHTTPRoute
   * @var Closure
   */
  private Closure $iRouteFactory;
  
  
  /**
   * 
   * @var array<int,array<string,list<array{0:Closure,1:array,2:array}>>>
   */
  private array $routes = [];
  
  
  /**
   * @param Closure|null $iRouteFactory fn( string $path, \Closure $endpoint, array $options, array $context ) : IHTTPRoute
   * @param bool $addContextToNamedRoutes Set to true and array $context argument is expected to exist on each route.
   */
  public function __construct( ?Closure $iRouteFactory = null )
  {
    if ( $iRouteFactory != null )
      $this->iRouteFactory = $iRouteFactory;
    else
      $this->iRouteFactory = self::createDefaultRouteFactory();
  }
  
  
  /**
   * @param bool $addContextToNamedArguments When true, the context array is added to the arguments array as 'context'
   * @return \Closure fn( string $path, \Closure $endpoint, array $options, array $context ) : IHTTPRoute
   * @static
   */
  public static final function createDefaultRouteFactory( bool $addContextToNamedArguments = false ) : \Closure
  {
    $handler = new FunctionRouteHandler( $addContextToNamedArguments );
    return static function( string $path, Closure $endpoint, array $options, array $context ) use($handler) : IHTTPRoute {
      /** @var class-string $class 
          @var array<string> $options 
          @var array<string,mixed> $context */        
      return new DefaultHTTPRoute( $handler, $path, $endpoint, $options, $context );
    };
  }
  
  
  /**
   * 
   * @param string $pattern
   * @param Closure $endpoint
   * @param array $options
   * @param array $context
   * @return static
   * @throws InvalidArgumentException
   */
  public function add( string $pattern, Closure $endpoint, array $options = [], array $context = [] ) : static
  {
    if ( empty( $pattern ))
      throw new InvalidArgumentException( 'pattern must not be empty' );
    
    $bucket = $this->getBucket( $pattern );
    
    
    $this->routes[$bucket][$pattern][] = [$endpoint, $options, $context];
    
    return $this;
  }

  
  /**
   * Retrieve a list of possible route patterns and configurations based on the supplied uri 
   * @param IHTTPRouteRequest $request 
   * @return \Generator<IHTTPRoute> Possible routes 
   */
  public function getPossibleRoutes( IHTTPRouteRequest $request ) : Generator
  {
    for ( $bucket = $this->getBucket( $request->getURI()); $bucket >= 0; $bucket-- )
    {
      if ( !isset( $this->routes[$bucket] ))
        continue;
      
      foreach( $this->routes[$bucket] as $path => $dataList )
      {
        foreach( $dataList as $data )
        {          
          yield $this->createRoute( $path, $data[0], $data[1], $data[2] );
        }
      }
    } 
  }
  
  
  /**
   * Creates an IHTTPRoute instance 
   * @param string $path The path/pattern
   * @param string $class Class name 
   * @param string $method method name 
   * @param array $options Options 
   * @param array $context Context array 
   * @return IHTTPRoute 
   * @throws RouteConfigurationException
   */
  protected function createRoute( string $path, Closure $endpoint, array $options, array $context ) : IHTTPRoute
  {
    $f = $this->iRouteFactory;
    $res = $f( $path, $endpoint, $options, $context );
    
    if ( !( $res instanceof IHTTPRoute ))
    {
      throw new RouteConfigurationException( 'Closure $iRouteFactory passed to ' . static::class 
        . '::__construct() must return an instance of ' . IHTTPRoute::class );
    }
    
    return $res;
  }
  
  
  /**
   * Get the route config data bucket.
   * This is to sort of reduce the number of route matches each request must make. 
   * Returns the number of PATH_DELIM in a given path.
   * @param string $path The path 
   * @return int bucket 
   */
  protected function getBucket( string $path ) : int
  {
    if ( $path == self::PATH_DELIM )
      return 0;
    
    return substr_count( $path, self::PATH_DELIM );
  }  
}
