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

use ReflectionClass;
use ReflectionMethod;

  
/**
 * Using the PHP Reflection API, determine the type, number and values of arguments used when invoking some method.
 */
interface IArgumentResolver
{
  /**
   * Prepare the class arguments for some constructor
   * @param ReflectionClass $c Constructor params 
   * @param array $context Route context
   * @return array prepared arguments.  This may be named or positional.
   */
  public function prepareClassArgs( ReflectionClass $c, array $context );
  
   
  /**
   * Given a reflection method and a list of arguments, 
   * figure out what arguments we're going to pass to the method when it is invoked.
   * 
   * @param ReflectionMethod $m Method
   * @param array $args Argument array
   * @param array $context route context (This may contain an array key self::C_ARGS_METHOD, which can contain 
   * more arguments to pass to the method)  
   * @return array Prepared arguments. This may be named or positional.
   */
  public function prepareMethodArgs( ReflectionMethod $m, array $args, array $context ) : array;
}
