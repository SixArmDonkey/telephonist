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

use InvalidArgumentException;


/**
 * Default route request implementation.
 */
class DefaultHTTPRouteRequest implements IHTTPRouteRequest
{
  private const REQUEST_URI = 'REQUEST_URI';
  
  /**
   * Reference to $_SERVER 
   * @var array
   */
  private array $server;
  
  /**
   * @param array<string,string> &$server $_SERVER 
   */
  public function __construct( array $server )
  {
    if ( !isset( $server[self::REQUEST_URI] ))
      throw new InvalidArgumentException( 'server must contain key "' . self::REQUEST_URI . '"' );
    
    $this->server = $server;
  }
  
  
  /**
   * Retrieve the request uri 
   * @return string
   */
  public function getURI() : string
  {
    if ( !isset( $this->server[self::REQUEST_URI] )
      || !is_string( $this->server[self::REQUEST_URI] ))
    {
      throw new \Exception( 'Server array is missing request uri.  This does not appear to be a web request' );
    }
    
    return $this->server[self::REQUEST_URI];
  }
  
  
  /**
   * Retrieve a list of all available headers.
   * @return array
   */
  public function getHeaders() : array
  {
    return $this->server;
  }
  
  
  /**
   * Retrieve a header value.
   * If the header is not present, this should return an empty string.
   * @param string $name Header name 
   * @return string
   */
  public function getHeader( string $name ) : string
  {
    if ( isset( $this->server[$name] )
      && is_string( $this->server[$name] ))
    {
      return $this->server[$name];
    }
    
    return '';
  }
}
