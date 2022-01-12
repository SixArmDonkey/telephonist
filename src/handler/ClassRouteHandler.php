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

use buffalokiwi\telephonist\RouteConfigurationException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;


/**
 * Given some route data, this will instantiate and invoke some method.
 * 
 * 
 */
class ClassRouteHandler implements IRouteHandler
{
  
  /**
   * The null type name as returned by ReflectionType::getName()
   */
  private const T_NULL = 'null';
  
  
  private IArgumentResolver $argResolver;
  
  
  /**
   * When true, the context array is added to the arguments array as 'context'
   * @var bool
   */
  private bool $addContextToNamedArguments;
  
  
  /**
   * 
   * @param bool $addContextToNamedArguments When true, the context array is added to the arguments array as 'context'
   */
  public function __construct( IArgumentResolver $argResolver, bool $addContextToNamedArguments = false )
  {
    $this->argResolver = $argResolver;
    $this->addContextToNamedArguments = $addContextToNamedArguments;
  }
    
  
  /**
   * 
   * @param class-string|object $resource The fully qualified class name 
   * @param string $identifier The method name 
   * @param array $args Arguments to be passed to the method endpoint.  If this is an associative array. 
   * named arguments will be used.
   * 
   * @param array $context Meta data.  This array may contain keys:
   * 
   * "args_class"  - Arguments passed to the class constructor defined by $class
   * "args_method" - Arguments passed to the method defined by $method
   * 
   * Arguments are processed and merged as follows:
   * 
   * 1) $methodArgs contains matches from some route pattern and contain user input.  
   * 2) Optionally, method arguments may be obtained from the $context array 
   *   a) Each element from $context[args_method] is added to $methodArgs 
   *   b) If all keys in $methodArgs are strings, then named arguments are used.  Otherwise, the keys
   *      of $methodArgs are replaced with sequential integers starting with zero, and the method arguments are passed 
   *      in the order they were received.
   *      
   * 3) Class arguments can be passed by $context[args_class] and/or entirely through reflection and some 
   *    sort service locator container.
   *   a) Arguments received via $context[args_class] can use either integer or string keys
   *      i) Entries with integer keys will be supplied directly to the class constructor at the 
   *         position defined by the key
   *     ii) Entries with string keys will be treated as named arguments.  
   *    iii) Entries with string values that start with a backslash (\) are first run through class_exists().  If the
   *         class exists, then the instantiateClass() method is called.  The result of this method must be an instance
   *         of the supplied type.
   */
  public function execute( string|object $resource, string $identifier = '', array $args = [], array $context = [] ) : mixed
  {
    if ( empty( $identifier ))
      throw new RouteConfigurationException( 'Method endpoint for ' . (( is_string( $resource )) ? $resource : get_class( $resource )) . ' must not be empty' );
    
    if ( $this->addContextToNamedArguments && !isset( $args['context'] ))
      $args['context'] = $context;
    
    if ( is_string( $resource ))
      return $this->executeClassString( $resource, $identifier, $args, $context );
    
    return $this->executeObject( $resource, $identifier, $args, $context );
  }
  
  
  /**
   * Invokes a method on an instance of some class object.
   * @param object $resource Class instance 
   * @param string $identifier Method name 
   * @param array $args Argument array 
   * @param array $context Route context array 
   * @return mixed route result 
   * @throws RouteConfigurationException
   */
  private function executeObject( object $resource, string $identifier = '', array $args = [], array $context = [] ) : mixed
  {
    $m = new ReflectionMethod( $resource, $identifier );
    
    if ( $m->isStatic())
    {
      throw new RouteConfigurationException( 'Cannot call static method on instantiated object of type ' 
        . get_class( $resource ));
    }
    
    return $m->invoke( $resource, ...$this->argResolver->prepareMethodArgs( $m, $args, $context ));
  }
  
  
  /**
   * Instantiates a class defined by $resource and invokes method $identifier with arguments $args 
   * @param string $resource class name
   * @param string $identifier method name 
   * @param array $args argument list  
   * @param array $context route context 
   * @return mixed route response 
   * @throws RouteConfigurationException
   */
  private function executeClassString( string $resource, string $identifier = '', array $args = [], array $context = [] ) : mixed
  {
    if ( !class_exists( $resource ))
      throw new RouteConfigurationException( 'Supplied class string: "' . $resource . '" is not an existing class' );
    
    $c = new ReflectionClass( $resource );
    
    try {
      $m = $c->getMethod( $identifier );
    } catch( ReflectionException ) {
      $m = null;
    }
    
    if ( is_null( $m ))
    {
      throw new RouteConfigurationException( 'Method "' . $identifier . '" is not a valid method of class '
        . '"' . $resource . '".' );
    }

    //..Get class constructor arguments    
    $cArgs = $this->argResolver->prepareClassArgs( $c, $context );
    
    //..Supplied method arguments merged with method arguments from context array and filtered 
    $mArgs = $this->argResolver->prepareMethodArgs( $m, $args, $context );
    
    if ( !$m->isStatic())
    {
      //..Execute the method 
      return $m->invoke( $c->newInstance( ...$cArgs ), ...$mArgs );
    }
    
    
    //..This is a static method 
    if ( sizeof( $cArgs ) > 0 )
    {
      throw new RouteConfigurationException( 'Route endpoint is static method "' . $m->getName() 
        . '" of class "' . $c->getName()
        . '", but class constructor arguments have been defined in the route '
        . 'context array.  Class constructors are never called when routing to a static method, and your '
        . 'intention may be unclear.  Either remove the class arguments, remove the static designation, or move the '
        . 'static method to a new static class with no constructor' );
    }
        
    return $m->invoke( null, ...$mArgs );
  }
  
}
