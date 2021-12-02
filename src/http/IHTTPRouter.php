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
 * Based on some request, locate and execute some resource(s) and return the response.
 */
interface IHTTPRouter 
{
  /**
   * Route some request 
   * @param IHTTPRouteRequest $request Request
   * @return mixed Processed content 
   */
  public function route( IHTTPRouteRequest $request ) : mixed;
}
