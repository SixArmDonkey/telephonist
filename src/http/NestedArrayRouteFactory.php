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
use function ctype_digit;


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
 *   ],
 *
 *   'path6' => [
 *     '(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']],
 *     '' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']]
 *   ]
 * ]
 */
abstract class NestedArrayRouteFactory implements IHTTPRouteFactory
{
  /**
   * Path delimiter
   */
  private const PATH_DELIM = '/';
  
  /**
   * Multi-route trigger.
   * This is a suffix appended to some path.
   * Values must be equal to an array:
   * 
   * 'pattern--END' => [
   *   [route config 1],
   *   [route config 2]
   * ]
   * 
   * This is for routes that may have the same uri but different options.  ie: different endpoints for get,post,etc
   *
   */
  private const MR_SUFFIX = '--END';
  
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
   * A list of valid route configuration tokens.
   * The order of this array determines the order of the route configuration data array when the array keys are integers
   */
  private const T_VALID = [self::T_CLASS, self::T_METHOD, self::T_OPTIONS, self::T_CONTEXT];
  
  
  /**
   * Produces a configuration array to be processed via processRouteConfig
   * @var IRouteConfig
   */
  private IRouteConfig $config;
  
  /**
   * If the route config has been processed, then this will be an array instead of null
   * @var array|null
   */
  private ?array $processedConfig = null;


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
  protected abstract function createRoute( string $path, string $class, string $method, array $options, array $context ) : IHTTPRoute;

    
  /**
   * @param IRouteConfig $config This must return a processable configuration array as defined in the docblock for this class.
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
    $config = $this->getProcessedConfig();
    
    $out = [];
    
    for ( $bucket = $this->getBucket( $request->getURI()); $bucket >= 0; $bucket-- )
    {
      if ( !isset( $config[$bucket] ))
        continue;
      
      foreach( $config[$bucket] as $path => $dataList )
      {
        foreach( $dataList as $data )
        {
          $out[] = $this->createRoute( $path, $data[self::T_CLASS], $data[self::T_METHOD], $data[self::T_OPTIONS], $data[self::T_CONTEXT] );
        }
      }
    } 
    
    return $out;      
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
   * Figures out what type of variable something is.
   * @param mixed $entry Something
   * @return string What it is
   * @final 
   */
  protected final function getGot( mixed $entry ) : string 
  {
    if ( is_array( $entry ))
      return 'array (' . sizeof( $entry ) . ')';
    else
      return (( is_null( $entry )) ? 'null' : (( is_object( $entry )) ? get_class( $entry ) : gettype( $entry )));
  }
  
  
  /**
   * This takes the multi-dimensional config array and flattens the keys and converts them into patterns.
   * 
   * ie: 
   * 
   * [
   *   'a' => [
   *     'b' => [route data],
   *     'c' => [
   *       'd' => [route data],
   *       '' => [route data]
   *     ]
   *   ]
   * ]
   * 
   * would convert into:
   * 
   * [
   *   'a/b => [[route data]],
   *   'a/c/d => [[route data]],
   *   'a/c => [[route data]]
   * ]
   * 
   * 
   * @return array
   */
  private function getProcessedConfig() : array
  {
    if ( !is_array( $this->processedConfig ))
    {
      $this->processedConfig = [];
      foreach( $this->processRouteConfigArray( '', $this->config->getConfig()) as $path => $data )
      {
        $bucket = $this->getBucket( $path );
        if ( !isset( $this->processedConfig[$bucket] ))
          $this->processedConfig[$bucket] = [];
        
        $this->processedConfig[$bucket][$path] = $data;
      }
    }
    
    return $this->processedConfig;
  }
  
  
  /**
   * Processes a route configuration array entry.
   * 
   * @param string $path Path 
   * @param array $config Config array 
   * @return array Processed array 
   * @throws RouteConfigurationException
   * @todo Worst documentation ever
   */
  private function processRouteConfigArray( string $path, array $config ) : array
  {
    if ( empty( $config ))
      return [];
    
    $out = [];
    
    //..Process multi-route configuration 
    if ( substr( $path, -5 ) == self::MR_SUFFIX )
    {
      return $this->processMultiRouteConfig( $path, $config );
    }
    else if ( $this->isRouteData( $path, $config ))
    {
      return [$path => $config];
    }

    
    //..Iterate over each config entry.
    //  $entryPath is either a string or an integer.  If it is a string, then we will assume that this is part of the
    //  path.  If it is an integer, then we will assume that it is part of the route information.
    foreach( $config as $entryPath => $entry )
    {
      if ( !is_string( $entryPath ) || !is_array( $entry ))
      {
        throw new RouteConfigurationException( 'Potential misconfigured route at "' . $path . '". '
          . ' Expected array with ' . sizeof( self::T_VALID ) . ' elements or an associative array with '
          . '(and only with) one or more of the following keys: "' 
          . implode( '","', self::T_VALID ) . '". ' 
          . ' Got ' . json_encode( $config ));
      }
      else if ( !empty( $entryPath ))
        $curPath = (( !empty( $path )) ? $path . self::PATH_DELIM : '' ) . $entryPath;
      else
        $curPath = $path;
      
      
      $res = $this->processRouteConfigArray( $curPath, $entry );
      if ( empty( $res ))
        continue;
      
      //..Ensure that each path is an array of route config data arrays
      foreach( $res as $k => $v )
      {
        if ( !isset( $out[$k] ))
          $out[$k] = [];
        
        if ( $this->isRouteData( $k, $v ))
        {
          //..Regular route config data array 
          $out[$k][] = $v;
        }
        else //..$v is always an array
        {
          //..This is probably a multi-route
          //..If so add each config array
          foreach( $v as $v1 )
          {
            if ( $this->isRouteData( $k, $v1 ))
              $out[$k][] = $v1;
          }
        }
        //..This may omit things missed by the above
      }
    }
    
    return $out;
  }
  
  
  /**
   * Route configuration handler for routes ending in --END.
   * 
   * This expects $config to be an array where each element is a valid route configuation data array.
   * 
   * @param string $path Route path
   * @param array $config Configuration array 
   * @return array Route data to be added to output array 
   * @throws RouteConfigurationException
   */
  private function processMultiRouteConfig( string $path, array $config ) : array
  {
    $out = [];
    $mrKey = substr( $path, 0, -5 );
    $out[$mrKey] = [];

    //..$entry should be an array of route configuration arrays 
    foreach( $config as $k => $routeConfig )
    {
      if ( !ctype_digit((string)$k ))
        throw new RouteConfigurationException( 'Routes ending with "--END" must not be equal to an associative array' );
      else if ( !$this->isRouteData( $path, $routeConfig ))
        throw new RouteConfigurationException( 'Multi-route configuration array at "' . $path . '" index "' . $k . '" does not appear to contain route configuration data.  '
          . ' Expected array with ' . sizeof( self::T_VALID ) . ' elements or an associative array with '
          . '(and only with) one or more of the following keys: "' 
          . implode( '","', self::T_VALID ) . '". ' 
          . ' got ' . json_encode( $routeConfig ));

      $out[$mrKey][] = $routeConfig;
    }    
    
    return $out;
  }  
  
  
  
  /**
   * Test if $entry contains route configuration data or if it is more nested routes.
   * 
   * A route configuration array will either be an array with exactly 4 elements: [string,string,string|array,array]
   * or an map with one or more of the following entries:
   * [
   *   'class' => string,
   *   'method' = string,
   *   'options' => string|array,
   *   'context' => array
   * ]
   * "class" is a required entry when not using numeric keys.
   * 
   * @param string $path Path
   * @param array $entry Entry 
   * @return bool will it blend?
   */
  private function isRouteData( string $path, array &$entry ) : bool
  {
    if ( empty( $entry ))
      return false;
    
    $keysAllInteger = true;
    
    foreach( array_keys( $entry ) as $k )
    {
      //..If this key is not an integer, then we need to check if the key is one of the configuration keys       
      if ( !ctype_digit((string)$k ))
      {
        $keysAllInteger = false;
        
        //..If this is a string key and it is not equal to one of the route config tokens, then this is not route data.
        if ( !in_array( $k, self::T_VALID ))
        {
          return false;
        }
        
        //..This key equals one of the route tokens, and MAY be route data.
      }
      else if ( !$keysAllInteger )
      {
        //..If the entry array contains mixed string and integer keys, this is not route configuration data 
        //..This one may be better as an exception
        return false;
      }      
    }
    
    
    //..Either all of the keys are strings and are equal to one of the tokens, or all of the keys are integers.
    
    
    if ( $keysAllInteger && sizeof( $entry ) != sizeof( self::T_VALID ))
    {
      //..If all keys are integers, the length of $entry must be equal to sizeof(T_VALID)
      return false;
    }

    //..We need to test that each key exists and is of the right type
    //..This is 2 passes because int keys are converted to strings.
    foreach( $entry as $k => $v )
    {
      switch( $k )
      {
        case 0; //..fall through
          $entry[self::T_CLASS] = $v;
          unset( $entry[$k] );
          
        case self::T_CLASS:
          if ( empty( $v ) || !is_string( $v ))
            throw new RouteConfigurationException( 'Value at Path: "' . $path . '" and Entry: "' . $k . '" must be a string' );
        break;

          
        case 1: //..fall through
          $entry[self::T_METHOD] = $v;
          unset( $entry[$k] );
          
        case self::T_METHOD:
          if ( !is_string( $v ))
            throw new RouteConfigurationException( 'Value at Path: "' . $path . '" and Entry: "' . $k . '" must be a string' );
        break;

        
        case 2: //..fall through
          $entry[self::T_OPTIONS] = $v;
          unset( $entry[$k] );
            
        case self::T_OPTIONS:
          if ( !is_string( $v ) && !is_array( $v ))
            throw new RouteConfigurationException( 'Value at Path: "' . $path . '" and Entry: "' . $k . '" must be a string or array' );
          
          if ( is_string( $v ))
          {
            if ( strpos( $v, ',' ) !== false )
              $entry[self::T_OPTIONS] = explode( ',', $v );
            else
              $entry[self::T_OPTIONS] = str_split( $v, 1 );
          }
        break;

        
        case 3: //..fall through
          $entry[self::T_CONTEXT] = $v;
          unset( $entry[$k] );
          
        case self::T_CONTEXT:
          if ( !is_array( $v ))
            throw new RouteConfigurationException( 'Value at Path: "' . $path . '" and Entry: "' . $k . '" must be an array' );
        break;

        //..Not route configuration 
        //..If the previous section works, this should be unreachable.
        default:
          return false;
      }
    }
    
    //..This is valid route configuration data 
    return true;
  }
}
