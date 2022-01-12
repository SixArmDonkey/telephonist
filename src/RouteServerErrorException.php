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

use Exception;
use Throwable;

class RouteServerErrorException extends Exception 
{
  public function __construct( string $message = 'Server Error', int $code = 500, Throwable $previous = null ) 
  {
    parent::__construct( $message, $code, $previous );
  }  
}
