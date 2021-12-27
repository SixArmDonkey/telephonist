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

use buffalokiwi\telephonist\RouteConfigurationException;
use buffalokiwi\telephonist\RouteValidationException;


/**
 * Restrict by request method
 */
class MethodRouteOption extends HTTPRouteOption
{
  public const GET = 'GET';
  public const POST = 'POST';
  public const PUT = 'PUT';
  public const PATCH = 'PATCH';
  public const DELETE = 'DELETE';
  public const OPTIONS = 'OPTIONS';
  public const HEAD = 'HEAD';
  
  public const VALID = [
    self::GET, 
    self::POST, 
    self::PUT, 
    self::PATCH, 
    self::DELETE, 
    self::OPTIONS, 
    self::HEAD
  ];
  
  private const K_REQUEST_METHOD = 'REQUEST_METHOD';
  
  
  /**
   * @param string $enabledMethods HTTP Request method strings.
   * @throws RouteConfigurationException
   */
  public function __construct( string ...$enabledMethods )
  {
    parent::__construct( ...(( empty( $enabledMethods )) ? self::VALID : $enabledMethods ));
    
    if ( !$this->validateEnabledMethods( ...$enabledMethods ))
    {
      throw new RouteConfigurationException( 'Invalid HTTP request method.  Valid methods are: "' 
        . implode( '","', self::VALID ) . '".' );
    }
  }
  
  
  /**
   * Validate this flag against the current request 
   * @param IHTTPRouteRequest $request 
   * @param IHTT{Route $route Route 
   * @return bool is valid.  This can throw an exception or return false to try a different route.
   * @throws RouteValidationException
   */
  public function validate( IHTTPRouteRequest $request, IHTTPRoute $route ) : bool
  {
    //..Get the current request method 
    $method = $request->getHeader( self::K_REQUEST_METHOD );
    
    foreach( $route->getOptions() as $opt )
    {
      if ( $opt == $method && in_array( $opt, $this->getCommand()))
        return true;
    }
    
    return false;
  }  
  
  
  /**
   * Check that all method strings are listed in VALID
   * @param string $enabledMethods
   * @return bool
   */
  private function validateEnabledMethods( string ...$enabledMethods ) : bool
  {
    foreach( $enabledMethods as $m )
    {
      if ( !in_array( $m, self::VALID ))
        return false;
    }
    
    return true;
  }
}
