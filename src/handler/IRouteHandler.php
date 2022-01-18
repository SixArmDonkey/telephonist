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

namespace buffalokiwi\telephonist\handler;


/**
 * Route handlers are responsible for locating some endpoint and returning the content.  This can be anything, a file,
 * class, some global function, an RPC, fried chicken, etc.
 */
interface IRouteHandler 
{
  /**
   * Execute some endpoint handler.  This will execute either resource or the identifier at resource.
   * @param class-string|object $resource Class name, file name, etc 
   * @param string $identifier Optional. When resource is class, this would be the method name
   * @param array $args Arguments
   * @param array $context Context 
   * @return mixed
   */  
  public function execute( string|object $resource, string $identifier = '', array $args = [], array $context = [] ) : mixed;
}
