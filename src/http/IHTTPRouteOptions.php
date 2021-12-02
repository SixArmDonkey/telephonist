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
 * Route options
 */
interface IHTTPRouteOptions
{
  /**
   * Retrieve a list of enabled options
   * @param string $option Zero or more options.  Passing zero returns all options.
   * @return IHTTPRouteOption[]
   * @throws InvalidArgumentException if the specified option does not exist 
   */
  public function getOptions( string ...$option ) : array;
  
  
  /**
   * Test if all of the supplied options are set 
   * @param string $option options list
   * @return bool
   * @throws InvalidArgumentException if any trimmed $option is an empty string 
   */
  public function hasOption( string ...$option ) : bool;
  
  
  /**
   * Test if any of the supplied options are set 
   * @param string $option option list 
   * @return bool
   */
  public function hasAny( string ...$option ) : bool;
}
