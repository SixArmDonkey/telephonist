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

interface IRouteMatcher 
{
  /**
   * 
   * @param string $pattern Some pattern 
   * @param string $uri The pattern subject 
   * @return array A map of matches
   * This may use integer keys for positional arguments or strings for named arguments
   */
  public function match( string $pattern, string $uri ) : array;
}