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

namespace buffalokiwi\telephonist;

use Closure;



/**
 * A route config implementation that uses a function to return some config array.
 */
class DefaultRouteConfig implements IRouteConfig
{
  private Closure $loadConfig;
  private ?array $configCache = null;
  
  
  /**
   * @param Closure $loadConfig f() : array
   * Returns an array containing something 
   */
  public function __construct( Closure $loadConfig )
  {
    $this->loadConfig = $loadConfig;
  }
  
  
  /**
   * Get a configuration array 
   * @return array
   */
  public function getConfig() : array
  {
    if ( is_array( $this->configCache ))
      return $this->configCache;
    
    $f = $this->loadConfig;
    $res = $f();
    
    if ( !is_array( $res ))
    {
      throw new RouteConfigurationException( 'Closure passed to ' . static::class 
        . '::__construct() must return an array. Got ' . 
        (( empty( $res )) ? 'null' : (( is_object( $res )) ? get_class( $res ) : gettype( $res ))));
    }
    
    $this->configCache = $res;
    return $res;        
  }
}
