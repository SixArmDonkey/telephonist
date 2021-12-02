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

namespace DefaultClassHandlerTest;

use buffalokiwi\telephonist\handler\DefaultClassHandler;
use buffalokiwi\telephonist\RouteConfigurationException;
use PHPUnit\Framework\TestCase;
use stdClass;



class ValidTestClass 
{
  public static function staticVoid() : void {}
  public static function staticString() : string { return 'staticString'; }
  public static function staticStringArg1( string $arg ) : string { return $arg; }
  public static function staticIntArg1( int $arg ) : int { return $arg; }
  public static function staticMultiArg( string $arg1, int $arg2, string $arg3 ) { return $arg1 . (string)$arg2 . $arg3; }
  public static function staticClassArg( stdClass $arg1 ) { return $arg1; }
  
  public function instanceString() : string { return 'instanceString'; }
  public function instanceStringArg1( string $arg ) : string { return $arg; }
  public function instaceIntArg1( int $arg ) : int { return $arg; }
  public function instanceMultiArg( string $arg1, int $arg2, string $arg3 ) { return $arg1 . (string)$arg2 . $arg3; }
  public function instanceClassArg( stdClass $arg1 ) { return $arg1; }
}

class InvalidStaticTestClass
{
  public function __construct( stdClass $arg ) {} 
  public static function staticVoid() : void {}
}


class DefaultClassHandlerTest extends TestCase
{
  
  private ?DefaultClassHandler $instance = null;
  
  
  public function setUp() : void
  {
    $this->instance = new DefaultClassHandler();
  }
  
  
  /**
   * Test execute()
   * 
   * 1) $class as an empty string throws a RouteConfigurationException
   * 2) $class as a non-empty string representing an invalid class throws a RouteConfigurationException
   * 
   * @return void
   */
  public function testExecuteClassMustNotBeEmptyAndMustExist() : void
  {
    try {
      $this->instance->execute( '', '', [], [] );
      $this->fail( 'Calling execute with $class equal to an empty string must throw a RouteConfigurationException' );
    } catch ( RouteConfigurationException ) {
      //..Expected
    }
    
    try {      
      $this->instance->execute( 'a' . uniqid(), '', [], [] );
      $this->fail( 'Calling execute with $class equal to invalid class name must throw a RouteConfigurationException' );
    } catch ( RouteConfigurationException ) {
      //..Expected
    }
    
    $this->expectNotToPerformAssertions();
  }
  
  
  public function testExecuteMethodMustNotBeEmptyAndMustExist() : void
  {
    try {
      $this->instance->execute( ValidTestClass::class, '', [], [] );
      $this->fail( 'Calling execute with $method equal to an empty string must throw a RouteConfigurationException' );
    } catch ( RouteConfigurationException ) {
      //..Expected
    }
    
    try {
      $this->instance->execute( ValidTestClass::class, 'a' . uniqid(), [], [] );
      $this->fail( 'Calling execute with $method equal to invalid metod name must throw a RouteConfigurationException' );
    } catch ( RouteConfigurationException ) {
      //..Expected
    }
    
    $this->expectNotToPerformAssertions();
  }
  
  
  public function testExecuteMethodStaticRoute() : void
  {
    $this->assertSame( 'staticString', $this->instance->execute( ValidTestClass::class, 'staticString', [], [] ));
  }
  
  
  public function testExecuteStaticStringArgument() : void
  {
    $this->assertSame( 'staticStringArg1', $this->instance->execute( ValidTestClass::class, 'staticStringArg1', ['staticStringArg1'] ));
    
    //..Test named
    $this->assertSame( 'staticStringArg1', $this->instance->execute( ValidTestClass::class, 'staticStringArg1', [ 'arg' => 'staticStringArg1'] ));
  }
  
  
  public function testExecuteStaticIntArgument() : void
  {
    $this->assertSame( 13, $this->instance->execute( ValidTestClass::class, 'staticIntArg1', [13] ));
    
    //..Test named
    $this->assertSame( 13, $this->instance->execute( ValidTestClass::class, 'staticIntArg1', ['arg' => 13] ));
  }
  
  
  public function testExecuteMultipleArgsStatic() : void
  {
    $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'staticMultiArg', ['arg1', 357, 'arg2'] ));
    
    //..named
    $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'staticMultiArg', ['arg1' => 'arg1', 'arg2' => 357, 'arg3' => 'arg2'] ));
    
    //..Positional mixed with named
    $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'staticMultiArg', [1 => 357, 'arg1' => 'arg1', 'arg3' => 'arg2'] ));
    
    //..Invalid positional mixed with named
    try {
      //..arg1 exists at position zero.  Therefore, supplying both zero and arg1 must throw an exception
      $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'staticMultiArg', [0 => 357, 'arg1' => 'arg1', 'arg3' => 'arg2'] ));
      $this->fail( 'When positional arguments are mixed with named arguments, each referenced argument must be unique or a RouteConfigurationEception must be thrown.' );
    } catch( RouteConfigurationException ) {
      //..Expected
    }
    
    //..Test creating an instance of a class
    $this->assertInstanceOf( stdClass::class, $this->instance->execute( ValidTestClass::class, 'staticClassArg' ));    
  }
  


  public function testExecuteMultipleArgsInstance() : void
  {
    $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'instanceMultiArg', ['arg1', 357, 'arg2'] ));
    
    //..named
    $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'instanceMultiArg', ['arg1' => 'arg1', 'arg2' => 357, 'arg3' => 'arg2'] ));
    
    //..Positional mixed with named
    $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'instanceMultiArg', [1 => 357, 'arg1' => 'arg1', 'arg3' => 'arg2'] ));
    
    //..Invalid positional mixed with named
    try {
      //..arg1 exists at position zero.  Therefore, supplying both zero and arg1 must throw an exception
      $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'instanceMultiArg', [0 => 357, 'arg1' => 'arg1', 'arg3' => 'arg2'] ));
      $this->fail( 'When positional arguments are mixed with named arguments, each referenced argument must be unique or a RouteConfigurationEception must be thrown.' );
    } catch( RouteConfigurationException ) {
      //..Expected
    }
    
    //..Test creating an instance of a class
    $this->assertInstanceOf( stdClass::class, $this->instance->execute( ValidTestClass::class, 'instanceClassArg' ));    
  }
  
  
  public function testExecuteMultipleArgsWithContextArgs() : void
  {
    $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'instanceMultiArg', ['arg1', 357], ['args_method' => [2 => 'arg2']] ));
    $this->assertSame( 'arg1357arg2', $this->instance->execute( ValidTestClass::class, 'instanceMultiArg', ['arg1', 357], ['args_method' => ['arg3' => 'arg2']] ));
  }

  
  public function testExecuteMethodInstanceRoute() : void
  {
    $this->assertSame( 'instanceString', $this->instance->execute( ValidTestClass::class, 'instanceString', [], [] ));
  }
  
  
  
  public function testStaticMethodWithConstructorArgumentsThrowsException() : void
  {
    $this->expectException( RouteConfigurationException::class );
    $this->instance->execute( InvalidStaticTestClass::class, 'staticVoid', [], [] );    
  }
}
