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

class RouteNotFoundException extends Exception 
{
  public function __construct( string $message = 'Not Found', int $code = 404, Throwable $previous = null ) 
  {
    parent::__construct( $message, $code, $previous );
  }  
}
