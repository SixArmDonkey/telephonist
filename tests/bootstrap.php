<?php
/**
 * This file is subject to the terms and conditions defined in
 * file 'LICENSE.txt', which is part of this source code package.
 *
 * Copyright (c) 2012-2020 John Quinn <john@retail-rack.com>
 * 
 * @author John Quinn
 */

declare( strict_types=1 );


class NestedArrayRouteFactory 
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
  private array $config;
  
  
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
  
  
  
  public function __construct( array $config )
  {
    $this->config = $config;
  }
 
  
  

  
  /**
   * With IRouteConfig, flatten down the configuration and place into buckets based on number of path delimiters
   * 
   * [
   *   'a' => [
   *     'b' => [data]
   *   ]
   * ]
   * 
   * 
   * @return void
   */
  public function initialize() : void
  {
    if ( $this->isInitialized )
      return;
    
    //..Process the configuration array 
    $this->routeBuckets = $this->flattenConfigurationArray( '', $this->config );
    
    $this->isInitialized = true;
  }
  

  
  
  /**
   * Retrieve a list of possible route patterns and configurations based on the supplied uri 
   * @param IHTTPRouteRequest $request 
   * @return IHTTPRoute[] Possible routes 
   */
  public function getPossibleRoutes( $uri ) : array
  {
    $this->initialize();
    
    
    $out = [];
    
    for ( $bucket = $this->getBucket( $uri ); $bucket >= 0; $bucket-- )
    {
      if ( !isset( $this->routeBuckets[$bucket] ))
        continue;
      
      
      foreach( $this->routeBuckets[$bucket] as $path => $routeList )
      {        
        
        foreach( $routeList as $data )
        {
          assert( is_string( $path ));
          assert( isset( $data[self::T_CLASS] ) && is_string( $data[self::T_CLASS] ));
          assert( isset( $data[self::T_METHOD] ) && is_string( $data[self::T_METHOD] ));
          assert( isset( $data[self::T_OPTIONS] ) && is_array( $data[self::T_OPTIONS] ));
          assert( isset( $data[self::T_CONTEXT] ) && is_array( $data[self::T_CONTEXT] ));

          if ( !is_array( $data ))
            continue;

          $out[] = [$path, (string)$data[self::T_CLASS], (string)$data[self::T_METHOD], $data[self::T_OPTIONS], $data[self::T_CONTEXT]];
          //$out[] = $this->createRoute( $path, (string)$data[self::T_CLASS], (string)$data[self::T_METHOD], $data[self::T_OPTIONS], $data[self::T_CONTEXT] );
        }
      }
    } 
    
    return $out;      
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
    
    $out[$this->getBucket( $path )][$path][] = $this->prepareRouteData( $data );
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
   * @param array<int, array<array-key, mixed>> $out
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
        $out[$bucket][$pPath] = $pData;
      }
    }
  }
  
  
  /**
   * @param string $path The starting path
   * @param array<string|int,array> $config
   * @return array<int, array<array-key, mixed>> $out

   */
  private function flattenConfigurationArray( string $path, array $config ) : array
  {
    $out = [];    
    
    foreach( $config as $pathPart => $data )
    {
      $this->processConfigEntry( $out, $path, $pathPart, $data );
    }
    
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
        $out[$this->getBucket( $curPath )][$curPath][] = $this->prepareRouteData( $data );
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
   * @param array<array-key,array|string> $data
   * @return bool
   */
  private function isRouteData( array $data ) : bool
  {
    foreach( $data as $k => $v )
    {
      $isKInt = is_int( $k );
      $isKStr = is_string( $k );
      
      if ( !$isKInt && !$isKStr )
        throw new RouteConfigurationException( 'Route configuration array keys must always be an integer or string.' );
      else if ( !$this->isNamedOrPositionalRouteDataValueValid( $k, $v ))
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
          $out[self::T_CLASS] = $v;
        break;

        //..Method
        case 1: //..fall through
        case self::T_METHOD:
          $out[self::T_METHOD] = $v;
        break;

        //..Options
        case 2: //..fall through
        case self::T_OPTIONS:
          if ( is_string( $v ))
            $out[self::T_OPTIONS] = explode( ',', $v );
          else
            $out[self::T_OPTIONS] = $v;
        break;

        //..Context
        case 3: //..fall through
        case self::T_CONTEXT:
          $out[self::T_CONTEXT] = $v;
        break;
      }
    }
    
    return $out;
  }  
}


  $a = [
    'path1' => ['class_name', 'method_name', ['opt1','opt2'], ['context array']],
    'path2' => ['class' => 'class_name', 'method' => 'method_name', 'options' => ['opt1', 'opt2'], 'context' => ['context_array']],
    'path2' => ['class' => 'class_name', 'method' => 'method_name', 'options' => ['opt1', 'opt2'], 'context' => ['context_array']],
 
    'path3\-with\-argument/(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']],
 
    'path4\-nested' => [
      '(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']]
     ],
      
    'path5' => [
      '(\d+)' => [
        ['class_name', 'method_name', ['method' => 'get'], ['context_array']],
        ['class_name', 'method_name', ['method' => 'post'], ['context_array']]
      ],
    ],
 
    'path6' => [
      '(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']],
      '' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']]
    ],
      
    'path7' => ['class', 'method']
  ];
  
  
  
  $a = [    'path5' => [
      '(\d+)' => [
        ['path5class', 'path5method', ['path5option1'], ['path5contextkey' => 'path5contextvalue']],
        ['path1class', 'path1method', ['path1option1'], ['path1contextkey' => 'path1contextvalue']]
      ],
    ]
];

  /*
  $c = new NestedArrayRouteFactory( $a );
  
  var_dump( $c->getPossibleRoutes( 'a/b' ));
  die;
*/


/*
class Foo
{
  public string $bar = 'baz';
  public string $baz = 'foo';
  public array $context = [1,2,3,4,5];
}

//$a = ['bar' => 'baz', 'baz' => 'foo'];




$things = [];
$tm = microtime(true);
for ( $i = 0; $i < 100000; $i++ )
{
  $things = ['bar' => 'baz', 'baz' => 'foo', 'context' => [1,2,3,4,5]];
}


$things = [];
echo ( microtime(true) - $tm ) . "\n\n";
$tm = microtime(true);


for ( $i = 0; $i < 100000; $i++ )
{
  $things = new Foo();
}


echo ( microtime(true) - $tm ) . "\n\n";


die;
*/
require_once( __DIR__ . '/../vendor/autoload.php' );
