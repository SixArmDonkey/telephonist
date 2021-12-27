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

use buffalokiwi\telephonist\RouteValidationException;


/**
 * If the XHR option is not enabled on the route, then this will validate as true.
 * If the XHR option is enabled on the route, then the X-Requested-With header must be present and equals to "XMLHTTPRequest"
 */
class XMLHTTPRequestRouteOption extends HTTPRouteOption
{
  public const COMMAND = 'XHR';
  
  private const REQUESTED_WITH = 'HTTP_X_REQUESTED_WITH';
  private const REQUESTED_WITH_XHR = 'XMLHTTPRequest';
  
  
  public function __construct( string $command = self::COMMAND )
  {
    parent::__construct( $command );
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
    if ( !in_array( self::COMMAND, $route->getOptions()))
      return true;
    
    return ( $request->getHeader( self::REQUESTED_WITH ) == self::REQUESTED_WITH_XHR );
  }
}
