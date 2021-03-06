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
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use \InvalidArgumentException;
use function ctype_digit;
use function \json_encode;


/**
 * Given some reflection stuff and possibly additional input arguments, convert them to
 * instances, named arguments, whatever we need to instantiate or invoke something.
 * 
 * (worst description ever)
 */
class ArgumentResolver implements IArgumentResolver
{
  /**
   * Context array key containing an array of class constructor arguments.
   * This should match the number of type of arguments defined by the constructor of the class endpoint.
   */
  public const C_ARGS_CLASS = 'args_class';
    
  /**
   * Context array key containing an array of method arguments.
   * This should match the number of type of arguments defined by the method endpoint.
   */
  public const C_ARGS_METHOD = 'args_method';
    
  /**
   * The string type name as returned by ReflectionType::getName()
   */
  private const T_SCALAR = ['bool', 'int', 'float', 'string', 'mixed'];
    
  /**
   * The array type name as returned by ReflectionType::getName()
   */
  private const T_ARRAY = 'array';
  
  /**
   * The null type name as returned by ReflectionType::getName()
   */
  private const T_NULL = 'null';
    
  
  
  public function __construct()
  {
    
  }
  
  
  /**
   * Prepare the class arguments for some constructor
   * @param ReflectionClass $c Constructor params
   * @param array $context Route context
   * @return array prepared arguments.  This may be named or positional.
   */
  public function prepareClassArgs( ReflectionClass $c, array $context ) : array
  {
    return $this->reflectionParametersToArgumentsArray(
      $this->getArgumentArray( self::C_ARGS_CLASS, $context ),
      ...( $c->getConstructor()?->getParameters() ?? [] )
    );
  }
  
   
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
  public function prepareMethodArgs( ReflectionMethod $m, array $args, array $context ) : array
  {
    $margs = $this->getArgumentArray( self::C_ARGS_METHOD, $context );
    foreach( $args as $k => $v )
    {
      switch( $k )
      {
        //..Sorry that things can't be named context
        case 'context': //..fall through
          break;
        
        default:
          $margs[$k] = $v;
      }
    }
    
    return $this->reflectionParametersToArgumentsArray(
      $margs,
      ...$m->getParameters()
    );


  }
  
  
  /**
   * Retrieve an instance of some class.
   * 
   * If you want to use some sort of DI container or whatever, simply override this and wire it to 
   * whatever get instance method on the container.
   * 
   * If $type is a valid class and the class constructor has no parameters, then this will return 
   * a new instance of $type.
   * @param string $type Class name 
   * @return object|null
   */
  protected function getInstance( string $type ) : ?object
  {    
    if ( $type == self::T_NULL || !class_exists( $type ))
      return null;
    
    $c = new ReflectionClass( $type );
    
    if ( sizeof( $c->getConstructor()?->getParameters() ?? [] ) == 0 )
    {
      return $c->newInstance();
    }
    
    return null;
  }  
  
  
  /**
   * Given a list of class names, call getInstance() for each and return the first non-null instance.
   * @param string $types
   * @return object
   * @psalm-suppress ArgumentTypeCoercion
   * @todo Fix this. Change $types to class-string 
   */
  private function getInstanceFromTypeList( string ...$types ) : ?object
  {
    $canBeNull = false;
    
    $instance = null;
    foreach( $types as $t )
    {
      if ( $t == self::T_NULL )
      {
        //..It is possible for this parameter to be null.
        $canBeNull = true;
        continue;
      }
      
      //..Try for an instance 
      $instance = $this->getInstance( $t );
      
      //..Psalm barks at this, but I think it's fine. We're testing for everything here.
      //..We cannot use class strings since this comes from a config array and is desiged to use strings representing
      //  class names. 
      if ( !empty( $instance ) && ( is_subclass_of( $instance, $t ) || ( is_a( $instance, $t ))))
        break;
    }
    
    if ( empty( $instance ) && !$canBeNull )
      throw new RouteHandlerException( 'Cannot create instance of ' . implode( '|', $types ));
      
    return $instance;
  }  
  
  
  /**
   * Given $args and $params, sort $args according to $params and return that array.
   * 
   * 1) Create an array ($out) the same length as $params
   * 2) Iterate over $params and create a map of param name => argument index value
   * 3) Iterate over $args and write to $out if empty:
   *   a) Values with numeric keys to $out
   *   b) Named argument values 
   * 4) If every value has not been assigned, throw an exception
   * 
   * @param array $args Method arguments 
   * @param ReflectionParameter $params Method argument meta data 
   * @return array<array-key|string,mixed|null|object> An array to use as method argument values when executing the method 
   */
  private function reflectionParametersToArgumentsArray( array $args, ReflectionParameter ...$params ) : array
  {
    if ( empty( $params ) && !empty( $args ))
      throw new RouteHandlerException( 'Arguments passed to constructor or method without arguments' );
    else if ( empty( $params ))
      return [];
    
    //..argument name to argument index map 
    //..If each entry was found
    //..Resulting argument array 
    list( $nameToArgumentIndexMap, $foundArguments, $out ) = $this->createArgumentMaps( $args, ...$params );
    
    //..Check if each argument was found in $args, and if not check for a default value and assign to $out if
    //  available.  If none of the above, throw a RouteConfigurationException.
    $notFound = [];
    foreach( $foundArguments as $name => $found )
    {
      //..Get the reflection parameter 
      
      /* @var ReflectionParameter $param */
      assert( is_string( $name ));
      assert( array_key_exists( $name, $nameToArgumentIndexMap ));
      
      $nk = $nameToArgumentIndexMap[$name];
      
      $param = $params[$nk];
      
      /* @var $param ReflectionParameter */
      
      //..Get the object types 
      $types = $this->getObjectParameterTypes( $name, $param );
      
      
      if ( $found //..Argument value was found prior to this
        && !empty( $types ) //..This parameter wants an object 
        && !empty( $out[$nameToArgumentIndexMap[$name]] )  //..The current value is not an instance of an object 
        && is_string( $out[$nameToArgumentIndexMap[$name]] ) //..The current value is a string (maybe a class name)
        && !( sizeof( $types ) == 1 && reset( $types ) == self::T_NULL )) //..Ensure that the only type is not null.  This may be a union type of string|null.
      {
        //..Try to convert to an instance
        $out[$nameToArgumentIndexMap[$name]] = $this->getInstanceFromTypeList( ...$types );
      }
      else if ( !$found && !empty( $types ))
      {
        //..An object argument without a value.
        //..This is where some service locator type thing could be called
        $out[$nameToArgumentIndexMap[$name]] = $this->getInstanceFromTypeList( ...$types );
      }
      else if ( !$found && $param->isDefaultValueAvailable())
      {
        //..Assign default value if available        
        /** @psalm-suppress MixedAssignment ReflectionParameter::getDefaultValue() returns mixed and we want that */
        //..We are going to depend on php to handle type checking when we invoke things
        $out[$nameToArgumentIndexMap[$name]] = $param->getDefaultValue();
      } 
      else if ( !$found )
      {
        //..No default value, this is gonna throw an exception.
        $notFound[] = $name;
      }
    }
    
    if ( !empty( $notFound ))
    {
      //..Add each unavailable argument name to the exception.
      throw new RouteConfigurationException( 'Method endpoint missing argument values for: "' 
        . implode( '","', $notFound ) . '".' );
    }
        
    //..Success
    return $out;
  }
  

  /**
   * Given some list of parameters, produce three maps:
   * 
   * 1) [parameter name => parameter position]
   * 2) [parameter name => argument was passed]
   * 3) [argument position => argument value]
   * 
   * @param array<array-key,mixed> $args 
   * @param ReflectionParameter $params 
   * @return array{0: array<string,array-key>, 1: array<array-key,bool>, 2: array<array-key,mixed|null|object>}
   */
  private function createArgumentMaps( array $args, ReflectionParameter ...$params ) : array
  {    
    //..argument name to argument index map 
    $nameToArgumentIndexMap = [];
    
    //..If each entry was found
    $foundArguments = [];
    
    //..Resulting argument array 
    $out = array_fill( 0, sizeof( $params ), null );    

    //..Initialize 
    foreach( $params as $k => $param )
    {
      //..Map of name to index 
      $nameToArgumentIndexMap[$param->getName()] = $k;
      
      //..Map of name to handled state 
      $foundArguments[$param->getName()] = false;
    }

    //..Map each supplied argument to the output array 
    //..Arguments with an integer key are mapped to that key in the output array
    //..Arguments with string keys are mapped to an integer value via $nameToArgumentIndexMap and mapped to that 
    //  result in the output array
    //..If any key of $args is an integer and not a key of $out or if a string and not a key of $nameToArgumentIndexMap
    //  a RouteConfigurationException is thrown      
    
    /** @psalm-suppress MixedAssignment This is a class loader.  Therefore we need mixed argument values */
    //..We are going to depend on php to handle type checking when we invoke things
    foreach( $args as $k => $v )
    {      
      if ( ctype_digit((string)$k ))
      {
        $this->throwValueExceptionWhenValueIsNull((int)$k, $out[$k] );
        
        $out[$k] = $v;
        $foundArguments[$params[$k]->getName()] = true;
      }
      else if ( is_string( $k ) && isset( $nameToArgumentIndexMap[$k] ))
      {
        $this->throwValueExceptionWhenValueIsNull( $k, $out[$nameToArgumentIndexMap[$k]] );
        
        $out[$nameToArgumentIndexMap[$k]] = $v;
        $foundArguments[$k] = true;
      }
      else
      {
        throw new RouteConfigurationException( 'Parameter "' . $k 
          . '" is not a valid argument.  Valid arguments are: "' 
          . implode( '","', array_keys( $nameToArgumentIndexMap )) . '". ' );
      }
    }    
    
    return [$nameToArgumentIndexMap, $foundArguments, $out];
  }
  
  
  /**
   * 
   * @param int|string $k
   * @param mixed $value
   * @return void
   * @throws RouteConfigurationException
   */
  private function throwValueExceptionWhenValueIsNull( int|string $k, mixed $value ) : void
  {
    if ( is_null( $value ))
      return;
    
    throw new RouteConfigurationException( 'Parameter "' . $k 
      . '" has already been assigned a value of "' 
      . json_encode( $value ));
  }
  
  
  /**
   * Retrieve a list of object parameter types for a given parameter.
   * This returns an empty string if the parameter types are scalar, array 
   * @param string $name
   * @param ReflectionParameter $param
   * @return array<string>
   * @throws UntypedArgumentException
   */
  private function getObjectParameterTypes( string $name, ReflectionParameter $param ) : array
  {
    /* @var ReflectionType $type */
    $type = $param->getType();
    if ( $type == null )
    {
      throw new UntypedArgumentException( $name );
    }
    else if ( $type instanceof ReflectionUnionType )
    {
      return $this->getNamesForUnionType( $type );
    }
    else if ( $type instanceof ReflectionNamedType )
    {
      $names = [];
      $this->addNameForNamedTypeToArray( $type, $names );
      return $names;
    }
    else
    {
      throw new UntypedArgumentException( $name 
        . ' type must be an instance of ReflectionUnionType or ReflectionNamedType' );
    }
  }
  
  
  /**
   * 
   * @param ReflectionUnionType $type
   * @return array<string>
   */
  private function getNamesForUnionType( ReflectionUnionType $type ) : array
  {
    $names = [];
    
    foreach( $type->getTypes() as $t )
    {        
      $this->addNameForNamedTypeToArray( $t, $names );
    }
    
    return $names;
  }
  
  
  /**
   * 
   * @param ReflectionNamedType $type
   * @param array<string> &$names
   * @return void
   */
  private function addNameForNamedTypeToArray( ReflectionNamedType $type, array &$names ) : void
  {
    if ( !in_array( $type->getName(), self::T_SCALAR ) && $type->getName() != self::T_ARRAY )
      $names[] = $type->getName();
  }
  
  
  /**
   * Retrieve the argument value array to pass to some constructor or function 
   * @param string $key The array key that should contain a map of arguments 
   * @param array $context Route context array 
   */
  private function getArgumentArray( string $key, array $context ) : array
  {
    if ( empty( $key ))
      throw new InvalidArgumentException( 'Key must not be empty' );
    else if ( !isset( $context[$key] ))
      return [];
    else if ( !is_array( $context[$key] ))
      throw new RouteConfigurationException( 'Context[' . $key . '] must be an array.' );
    
    return $context[$key];
  }    
}

