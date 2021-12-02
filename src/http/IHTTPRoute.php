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


interface IHTTPRoute
{
  /**
   * Test if some uri matches a regular expression.
   * 
   * This does NOT test if the selected route options exist or if they are valid or anything.
   * That is dependent on the router implementation 
   * 
   * @param IHTTPRouteRequest $uri
   * @param array &$matchedValues Matched argument values.  
   * @return bool If the route matches 
   */
  public function matches( IHTTPRouteRequest $request, array &$matchedValues ) : bool;
  
  
  /**
   * Execute the route 
   * @param array $matchedValues The arguments passed to the endpoint.  If the keys are strings, then 
   * we must match the argument name to the name of some method or function argument via reflection.  If the keys are
   * numeric, then the arguments are simply passed to the method or function as is.  
   * @return mixed
   */
  public function execute( array $matchedValues ) : mixed;
  
  
  /**
   * Route context
   * @return array
   */
  public function getContext() : array;
  
  
  /**
   * Route options   
   * @return array
   */
  public function getOptions() : array;
}
