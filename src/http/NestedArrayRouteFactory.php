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

use buffalokiwi\telephonist\IRouteConfig;
use buffalokiwi\telephonist\RouteConfigurationException;


/**
 * Converts a configuration array into IHTTPRoute instances on request.  This is used to provide potential 
 * endpoint choices to the router.
 * 
 * Example Route Configuration:
 * [
 *   'path1' => ['class_name', 'method_name', ['opt1','opt2'], ['context array']],
 *   'path2' => ['class' => 'class_name', 'method' => 'method_name', 'options' => ['opt1', 'opt2'], 'context' => ['context_array']],
 *
 *   'path3\-with\-argument/(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']],
 *
 *   'path4\-nested' => [
 *     '(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']]
 *    ],
 *
 *   'path5' => [
 *     '(\d+)--END' => [
 *       ['class_name', 'method_name', ['method' => 'get'], ['context_array']],
 *       ['class_name', 'method_name', ['method' => 'post'], ['context_array']]
 *     ],
 *     '' => ['class_name', 'method_name', ['method' => 'get'], ['context_array']]
 *   ],
 *
 *   'path6' => [
 *     '(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']],
 *     '' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']]
 *   ]
 * ]
 * 
 * 
 * Each "level" is another section of the path and is concatenated using the path delimiter to form a 
 * route pattern.  For example, after processing the above, we get:
 *
 * [
 * ...
 * path5/(\d+) => [[ route data ],[ route data ]],
 * path5 => [ route data ]
 * ...
 * ]
 * 
 * 
 */
abstract class NestedArrayRouteFactory implements IHTTPRouteFactory
{
  /**
   * Path delimiter
   */
  private const PATH_DELIM = '/';
  
  /**
   * Route configuration token: class name or filename 
   */
  private const T_CLASS = 'class';
  
  /**
   * Route configuration token: class method 
   */
  private const T_METHOD = 'method';
  
  /**
   * Route configuration token: route options string or options array 
   */
  private const T_OPTIONS = 'options';
  
  /**
   * Route configuration token: Route context array 
   */
  private const T_CONTEXT = 'context';
  
  
  /**
   * Produces a configuration array to be processed via processRouteConfig
   * @var IRouteConfig
   */
  private IRouteConfig $config;
  
  
  /**
   * A map of [bucket => [path => [route config array]]
   * @var array<int,array<array-key,array{0?:string, class?:string, 1?:string, method?:string, 2?string|array, options?:string|array, 3?:array<string,mixed>, context?:array<string,mixed>}>>
   */
  private array $routeBuckets = [];
  
  
  /**
   * If the $routeBuckets array has been initialized
   * @var bool
   */
  private bool $isInitialized = false;
  
  
  

  /**
   * Creates an IHTTPRoute instance.
   * 
   * The route instance is responsible for instantiating and invoking whatever endpoint
  * 
   * @param string $path The path/pattern
   * @param string $class Class name 
   * @param string $method method name 
   * @param array $options Options 
   * @param array $context Context array 
   * @return IHTTPRoute 
   * @throws RouteConfigurationException
   */
  protected abstract function createRoute( string $path, string $class, string $method, array $options, array $context ) : IHTTPRoute;
  
  
  /**
   * @param IRouteConfig $config Where route data comes from 
   */
  public function __construct( IRouteConfig $config )
  {
    $this->config = $config;
  }
 
  
  /**
   * Retrieve a list of possible route patterns and configurations based on the supplied uri 
   * @param IHTTPRouteRequest $request 
   * @return IHTTPRoute[] Possible routes 
   */
  public function getPossibleRoutes( IHTTPRouteRequest $request ) : array
  {
    $this->initialize();
    
    
    $out = [];
    
    for ( $bucket = $this->getBucket( $request->getURI()); $bucket >= 0; $bucket-- )
    {
      if ( !isset( $this->routeBuckets[$bucket] ))
        continue;
      
      
      foreach( $this->routeBuckets[$bucket] as $path => $routeList )
      {        
        
        foreach( $routeList as $data )
        {
          //..Even though this is a public method, the assertions are simply checking results from private methods.
          //  The following assertions are used to double check the route data array and to please psalm.
          assert( is_string( $path ));
          assert( isset( $data[self::T_CLASS] ) && is_string( $data[self::T_CLASS] ));
          assert( isset( $data[self::T_METHOD] ) && is_string( $data[self::T_METHOD] ));
          assert( isset( $data[self::T_OPTIONS] ) && is_array( $data[self::T_OPTIONS] ));
          assert( isset( $data[self::T_CONTEXT] ) && is_array( $data[self::T_CONTEXT] ));

          $out[] = $this->createRoute( $path, $data[self::T_CLASS], $data[self::T_METHOD], $data[self::T_OPTIONS], $data[self::T_CONTEXT] );
        }
      }
    } 
    
    return $out;      
  } 
  
  
  /**
   * With IRouteConfig, flatten down the configuration and place into buckets based on number of path delimiters
   * @return void
   */
  private function initialize() : void
  {
    if ( $this->isInitialized )
      return;
    
    //..Process the configuration array 
    $this->routeBuckets = $this->flattenConfigurationArray( '', $this->config->getConfig());
    
    $this->isInitialized = true;
  }

  
  /**
   * 
   * @param array $out
   * @param string $path
   * @param int $position
   * @param array $data
   * @throws RouteConfigurationException
   */
  private function mergeMulti( array &$out, string $path, int $position, array $data ) : void
  {
    if ( !$this->isRouteData( $data ))
    {
      throw new RouteConfigurationException( "Route data at " . $path . " position " 
        . (string)$position . " must be route data" );  
    }
    
    $bucket = $this->getBucket( $path );
    
    if ( !isset( $out[$bucket] ) || !is_array( $out[$bucket] ))
      $out[$bucket] = [];
    
    if ( !isset( $out[$bucket][$path] ) || !is_array( $out[$bucket][$path] ))
      $out[$bucket][$path] = [];
    
    $out[$bucket][$path][] = $this->prepareRouteData( $data );
  }
  
  
  /**
   * 
   * @param string $path
   * @param string $pathPart
   * @return string
   */
  private function mergePathPart( string $path, string $pathPart ) : string
  {
    if ( strlen( $pathPart ) > 0 )
      return (( !empty( $path )) ? $path . self::PATH_DELIM : '' ) . $pathPart;
    else
      return $path;    
  }


  /**
   * 
   * @param array $out
   * @param string $path
   * @param array $config
   * @return void
   */
  private function mergeFlattenConfigurationArray( array &$out, string $path, array $config ) : void
  {
    foreach( $this->flattenConfigurationArray( $path, $config ) as $bucket => $pConfig )
    {
      foreach( $pConfig as $pPath => $pData )
      {
        if ( !isset( $out[$bucket] ) || !is_array( $out[$bucket] ))
          $out[$bucket] = [];
        
        $out[$bucket][$pPath] = $pData;
      }
    }
  }
  
  
  /**
   * @param string $path The starting path
   * @param array $config
   * @return array<int,array<array-key,array{0?:string, class?:string, 1?:string, method?:string, 2?string|array, options?:string|array, 3?:array<string,mixed>, context?:array<string,mixed>}>>
   */
  private function flattenConfigurationArray( string $path, array $config ) : array
  {
    $out = [];
    
    foreach( $config as $pathPart => $data )
    {
      if ( !is_array( $data ))
        throw new RouteConfigurationException( "route configuration at " . $path . self::PATH_DELIM . (string)$pathPart . ' must be an array' );
      $this->processConfigEntry( $out, $path, $pathPart, $data );
    }
    
    /** @var array<int,array<array-key,array{0?:string, class?:string, 1?:string, method?:string, 2?string|array, options?:string|array, 3?:array<string,mixed>, context?:array<string,mixed>}>> $out */
    return $out;
  }
  
  
  /**
   * 
   * @param array $out
   * @param string $path
   * @param string|int $pathPart
   * @param array $data
   * @return void
   */
  private function processConfigEntry( array &$out, string $path, string|int $pathPart, array $data ) : void
  {
    if ( is_int( $pathPart ))
    {
      $this->mergeMulti( $out, $path, $pathPart, $data );
    }
    else
    {
      $curPath = $this->mergePathPart( $path, $pathPart );

      if ( $this->isRouteData( $data ))
      {      
        $bucket = $this->getBucket( $curPath );
        
        if ( !isset( $out[$bucket] ) || !is_array( $out[$bucket] ))
          $out[$bucket] = [];
        
        if ( !isset( $out[$bucket][$curPath] ) || !is_array( $out[$bucket][$curPath] ))
          $out[$bucket][$curPath] = [];
        
        $out[$bucket][$curPath][] = $this->prepareRouteData( $data );
      }
      else
      {
        $this->mergeFlattenConfigurationArray( $out, $curPath, $data );
      }
    }    
  }
  
  
  /**
   * Get the route config data bucket.
   * This is to sort of reduce the number of route matches each request must make. 
   * Returns the number of PATH_DELIM in a given path.
   * @param string $path The path 
   * @return int
   */
  protected function getBucket( string $path ) : int
  {
    if ( $path == self::PATH_DELIM )
      return 0;
    
    return substr_count( $path, self::PATH_DELIM );
  }
  
  
  /**
   * @param array $data
   * @return bool
   */
  private function isRouteData( array $data ) : bool
  {
    foreach( $data as $k => $v )
    {
      if ( !is_array( $v ) && !is_string( $v ))
        throw new RouteConfigurationException( 'Route configuration array values must always be an array or string.' );
      
      if ( !$this->isNamedOrPositionalRouteDataValueValid( $k, $v ))
        return false;
    }
    
    return true;
  }
  
  
  /**
   * For some array key/value pair where they key is an integer, test if the value is a string or array 
   * and that the value type matches the appropriate position.  (see T_VALID)
   * @param int|string $k array key 
   * @param array|string $v array value 
   * @return boolean
   */
  private function isNamedOrPositionalRouteDataValueValid( int|string $k, array|string $v ) : bool
  {
    switch( $k )
    {
      //..Class
      case 0: //..fall through
      case self::T_CLASS:
        return is_string( $v );

      //..Method
      case 1: //..fall through
      case self::T_METHOD:
        return is_string( $v );
        
      //..Options
      case 2: //..fall through
      case self::T_OPTIONS:
        return true;  //..This is always an array or a a string.

      //..Context
      case 3: //..fall through
      case self::T_CONTEXT:
        return is_array( $v );
    }
    
    //..Invalid
    return false;
  }
  
  
  /**
   * 
   * @param array<array-key,mixed> $data 
   * @return array<string,string|array> prepared route data 
   */
  private function prepareRouteData( array $data )
  {
    $out = [];
    
    foreach( $data as $k => $v )
    {
      switch( $k )
      {
        //..Class
        case 0: //..fall through
        case self::T_CLASS:
          assert( is_string( $v ));
          $out[self::T_CLASS] = $v;
        break;

        //..Method
        case 1: //..fall through
        case self::T_METHOD:
          assert( is_string( $v ));
          $out[self::T_METHOD] = $v;
        break;

        //..Options
        case 2: //..fall through
        case self::T_OPTIONS:
          assert( is_string( $v ) || is_array( $v ));
          if ( is_string( $v ))
            $out[self::T_OPTIONS] = explode( ',', $v );
          else
            $out[self::T_OPTIONS] = $v;
        break;

        //..Context
        case 3: //..fall through
        case self::T_CONTEXT:
          assert( is_array( $v ));
          $out[self::T_CONTEXT] = $v;
        break;
      }
    }
    
    return $out;
  }  
}
