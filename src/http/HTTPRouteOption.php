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
use function ctype_alnum;


/**
 * Base class for http route options.
 * This implements getCommand().
 */
abstract class HTTPRouteOption implements IHTTPRouteOption
{
  /**
   * 
   * @var array<string>
   */
  private array $command;
  
  public function __construct( string ...$command )
  {
    foreach( $command as $c )
    {
      if ( empty( $c ) || !ctype_alnum( $c ))
        throw new InvalidArgumentException( 'All commands must be alphanumeric' );
    }
    
    $this->command = $command;
  }
  
  
  /**
   * Retrieve the command string(s) used to trigger this option
   * @return array<string>
   * @final
   */
  public final function getCommand() : array
  {
    return $this->command;
  }
}
