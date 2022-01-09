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


/**
 * A route request for http messages.
 * 
 * This is simply a starting point.  Feel free to extend this to add
 * whatever type of request stuff you need to do things.
 * 
 */
interface IHTTPRouteRequest 
{
  /**
   * Retrieve the request uri 
   * @return string
   */
  public function getURI() : string;
  
  
  /**
   * Retrieve a list of all available headers.
   * This can simply be a copy of $_SERVER.
   * @return array
   */
  public function getHeaders() : array;
  
  
  /**
   * Retrieve a header value.
   * If the header is not present, this should return an empty string.
   * @param string $name Header name 
   * @return string
   */
  public function getHeader( string $name ) : string;  
}
