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
use buffalokiwi\telephonist\RouteNotFoundException;


/**
 * Default router implementation
 * Pass a request to route() and receive a result to send to the client.
 * 
 * The result of route() can be anything.  A string, object, whatever you want.  The router simply connects 
 * point A to point B.
 */
class DefaultHTTPRouter implements IHTTPRouter
{
  private IHTTPRouteFactory $routeFactory;
  private IHTTPRouteOptions $options;
  private bool $strict;
  
  
  /**
   * @param IHTTPRouteFactory $routeFactory Locates and creates IHTTPRoute instances based on a client request
   * @param IHTTPRouteOptions $options Enabled options are compared against listed route options.  
   * @param bool $strict When true, all options listed on any route must match a route option 
   * supplied to this constructor.  
   */
  public function __construct( IHTTPRouteFactory $routeFactory, IHTTPRouteOptions $options, bool $strict = true )
  {
    $this->routeFactory = $routeFactory;
    $this->options = $options;
    $this->strict = $strict;
  }
  
  
  /**
   * Route some request 
   * @param IHTTPRouteRequest $request Request
   * @return mixed Processed content 
   */
  public function route( IHTTPRouteRequest $request ) : mixed
  {
    //..Find any possible routes
    foreach( $this->routeFactory->getPossibleRoutes( $request ) as $route )
    {
      //..Matched arguments come from here 
      $matchedValues = [];
      
      //..Check if it matches and if so that the options match too.
      if ( $route->matches( $request, $matchedValues )
        && $this->validRouteOptions( $request, $route ))
      {
        //..Woohoo?
        return $route->execute( $matchedValues );
      }
    }
    
    throw new RouteNotFoundException();
  }
  
  
  /**
   * For all options listed on the supplied route, match them against the list of enabled options in the router and 
   * validate the route against the matches.  If a listed option on a route does not exist, and strict mode is enabled, 
   * then an exception is thrown, otherwise it skips the mission option and validation continues.
   * 
   * @param IHTTPRouteRequest $request
   * @param IHTTPRoute $route
   * @return bool
   * @throws RouteConfigurationException
   */
  private function validRouteOptions( IHTTPRouteRequest $request, IHTTPRoute $route ) : bool
  {
    foreach( $route->getOptions() as $opt )
    {
      if ( $this->options->hasOption( $opt ))
      {
        return $this->isOptionValid( $request, $route, $opt );
      }
      else if ( $this->strict )
      {
        throw new RouteConfigurationException( 'The requested route lists option "' . $opt 
          . '", which is not currently configured within this router.' );
      }
    }
    
    return true;
  }
  
  
  /**
   * Ensure that the supplied option validates if it is connected to this route
   * @param IHTTPRouteRequest $request
   * @param IHTTPRoute $route
   * @param string $opt
   * @return bool
   */
  private function isOptionValid( IHTTPRouteRequest $request, IHTTPRoute $route, string $opt ) : bool
  {
    //..The option is enabled in the router.  
    foreach( $this->options->getOptions( $opt ) as $o )
    {
      if ( !$o->validate( $request, $route ))
        return false;
    }
    
    return true;    
  }
}
