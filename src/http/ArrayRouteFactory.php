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

use buffalokiwi\telephonist\handler\ArgumentResolver;
use buffalokiwi\telephonist\handler\ClassRouteHandler;
use buffalokiwi\telephonist\IRouteConfig;
use buffalokiwi\telephonist\RouteConfigurationException;
use Closure;


/**
 * 
 */
class ArrayRouteFactory extends NestedArrayRouteFactory
{
  /**
   * Closure for creating instances of IHTTPRoute.
   * fn( string $class, string $method, array $options, array $context ) : IHTTPRoute
   * @var Closure
   */
  private Closure $iRouteFactory;
  
  
  /**
   * @param IRouteConfig $config This must return a processable configuration array as defined in the docblock for this class.
   * @param Closure $iRouteFactory fn( string $path, string $class, string $method, array $options, array $context ) : IHTTPRoute
   */
  public function __construct( IRouteConfig $config, ?Closure $iRouteFactory = null ) 
  {
    parent::__construct( $config );
    
    if ( $iRouteFactory instanceof Closure )
      $this->iRouteFactory = $iRouteFactory;
    else
      $this->iRouteFactory = self::createDefaultRouteFactory();
  }
  
  
  /**
   * Creates a default route factory
   * @param bool $addContextToNamedArguments When true, the context array is added to the arguments array as 'context'
   * @return \Closure fn( string $path, string $class, string $method, array $options, array $context ) : IHTTPRoute
   */
  public static final function createDefaultRouteFactory( bool $addContextToNamedArguments = false ) : \Closure 
  {
    $handler = new ClassRouteHandler( new ArgumentResolver(), $addContextToNamedArguments );
    return static function( string $path, string $class, string $method, array $options, array $context ) use($handler) : IHTTPRoute {
      /** @var class-string $class 
          @var array<string> $options 
          @var array<string,mixed> $context */
      return new ClassHTTPRoute( $handler, $path, $class, $method, $options, $context );
    };
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
  protected function createRoute( string $path, string $class, string $method, array $options, array $context ) : IHTTPRoute
  {
    $f = $this->iRouteFactory;
    $res = $f( $path, $class, $method, $options, $context );
    
    if ( !( $res instanceof IHTTPRoute ))
    {
      throw new RouteConfigurationException( 'Closure $iRouteFactory passed to ' . static::class 
        . '::__construct() must return an instance of ' . IHTTPRoute::class  );
    }
    
    return $res;
  }
}
