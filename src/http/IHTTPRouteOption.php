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
 * A router plugin used to add flags to route configuration entries.
 */
interface IHTTPRouteOption
{
  /**
   * Retrieve the command string(s) used to trigger this option
   * @return string[]
   */
  public function getCommand() : array;
  
  
  /**
   * Validate this flag against the current request 
   * @param IHTTPRouteRequest $request 
   * @param IHTT{Route $route Route 
   * @return bool is valid.  This can throw an exception or return false to try a different route.
   * @throws RouteValidationException
   */
  public function validate( IHTTPRouteRequest $request, IHTTPRoute $route ) : bool;
}
