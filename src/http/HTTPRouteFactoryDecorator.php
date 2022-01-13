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
 * HTTP Route factory decorator 
 */
class HTTPRouteFactoryDecorator implements IHTTPRouteFactory
{
  private IHTTPRouteFactory $factory;
  
  
  /**
   * @param IHTTPRouteFactory $factory Method calls are proxied to this object
   */
  public function __construct( IHTTPRouteFactory $factory )
  {
    $this->factory = $factory;
  }
  
  
  /**
   * Retrieve a list of possible route patterns and configurations based on the supplied uri 
   * @param IHTTPRouteRequest $request 
   * @return \Generator<IHTTPRoute> Possible routes 
   */
  public function getPossibleRoutes( IHTTPRouteRequest $request ) : \Generator
  {
    return $this->factory->getPossibleRoutes( $request );
  }
}
