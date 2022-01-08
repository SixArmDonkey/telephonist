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
 * IHTTPRoute implementation are responsible for executing routes.  This means making the final connection to 
 * some endpoint and returning the response.  The issue is that the current route implementation is not extensible.
 * Therefore, this handler object is to be used inside of the IHTTPRoute::execute() method.
 * 
 * Some handlers:
 * 
 * 1) Default Class Handler - Class constructor and method arguments are passed via the context 
 *    array keys 'args_class' and 'args_method'.
 * 2) DI Class Handler - An extension of the default handler, which will acquire arguments from some di container when
 *    context argument keys do not exist.
 * 3) Procedural Handler - Maps $class to some php file 
 * 4) Functional Handler - Maps $class to some array key containing closures. 
 * 5) Web service Handler?
 * 6) Redirect handler?
 * 
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
