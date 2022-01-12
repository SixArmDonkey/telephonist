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

use buffalokiwi\telephonist\http\IHTTPRoute;
use buffalokiwi\telephonist\http\IHTTPRouteFactory;
use buffalokiwi\telephonist\http\IHTTPRouteRequest;
use InvalidArgumentException;


/**
 * Used to attach multiple route factories to a single router
 * When getPossibleRoutes() is called, the same method is called on each each factory 
 * in the order it was supplied.
 */
class HTTPRouteFactoryGroup implements IHTTPRouteFactory
{
  /**
   * A list of factories.
   * 
   * @var array<IHTTPRouteFactory>
   */
  private array $factoryList;
  
  
  /**
   * @param IHTTPRouteFactory $factoryList Factories to use 
   * @throws InvalidArgumentException At least one factory is required 
   */
  public function __construct( IHTTPRouteFactory ...$factoryList )
  {
    if ( empty( $factoryList ))
      throw new InvalidArgumentException( 'factoryList must not be null' );
    
    $this->factoryList = $factoryList;
  }
  
  
  /**
   * Retrieve a list of possible route patterns and configurations based on the supplied uri 
   * @param IHTTPRouteRequest $request 
   * @return array<IHTTPRoute> Possible routes 
   */
  public function getPossibleRoutes( IHTTPRouteRequest $request ) : array
  {
    /** @var array<IHTTPRoute> $out */
    $out = [];
    
    foreach( $this->factoryList as $factory )
    {
      foreach( $factory->getPossibleRoutes( $request ) as $route )
      {
        assert( $route instanceof IHTTPRoute );
        
        $out[] = $route;
      }
    }
    
    return $out;
  }  
}
