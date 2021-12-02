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

use buffalokiwi\telephonist\handler\IRouteHandler;
use buffalokiwi\telephonist\http\ClassHTTPRoute;
use buffalokiwi\telephonist\http\IHTTPRouteRequest;
use PHPUnit\Framework\TestCase;


class ClassHTTPRouteTest extends TestCase
{
  public function testConstructorThrowsInvalidArgumentException() : void
  {
    $mockHandler = $this->getMockBuilder( IRouteHandler::class )->getMock();
    
    try {
      new ClassHTTPRoute( $mockHandler, '', 'class' );
      $this->fail( 'When $path is empty InvalidArgumentException must be thrown' );
    } catch ( InvalidArgumentException ) {
      //..Expected
    }
    
    
    try {
      new ClassHTTPRoute( $mockHandler, 'path', '' );
      $this->fail( 'When $class is empty InvalidArgumentException must be thrown' );
    } catch ( InvalidArgumentException ) {
      //..Expected
    }

    $this->expectNotToPerformAssertions();
  }
  
  
  public function testMatchesCanMatchPattern() : void
  {
    $mockHandler = $this->getMockBuilder( IRouteHandler::class )->getMock();
    $mockRequest = $this->getMockBuilder( IHTTPRouteRequest::class )->getMock();
    $mockRequest->method( 'getURI' )->willReturn( '/test' );
    $matchedValues = [];
    
    //..Test an exact match 
    $this->assertTrue(( new ClassHTTPRoute( $mockHandler, 'test', 'class', 'method' ))->matches( $mockRequest, $matchedValues ));

    //..Test an exact match 
    $this->assertFalse(( new ClassHTTPRoute( $mockHandler, 'invalid', 'class', 'method' ))->matches( $mockRequest, $matchedValues ));
    
    //..Test a exact match with leading slash
    $this->assertTrue(( new ClassHTTPRoute( $mockHandler, '/test', 'class', 'method' ))->matches( $mockRequest, $matchedValues ));
    
    //..Test a simple pattern 
    $this->assertTrue(( new ClassHTTPRoute( $mockHandler, '[a-z]+', 'class', 'method' ))->matches( $mockRequest, $matchedValues ));    
    
    //..Test capture group
    $this->assertTrue(( new ClassHTTPRoute( $mockHandler, '([a-z]+)', 'class', 'method' ))->matches( $mockRequest, $matchedValues ));    
    $this->assertCount( 1, $matchedValues );
    $this->assertSame( 'test', reset( $matchedValues ));
    
    //..Test named capture group via context array 
    $this->assertTrue(( new ClassHTTPRoute( $mockHandler, '([a-z]+)', 'class', 'method', [], ['args_captured' => ['arg1']] ))->matches( $mockRequest, $matchedValues ));    
    
    $this->assertCount( 1, $matchedValues );
    $this->assertSame( 'test', reset( $matchedValues ));
    $this->assertSame( 'arg1', array_key_first( $matchedValues ));
    
    //..Test named capture group with invalid uri 
    $this->assertFalse(( new ClassHTTPRoute( $mockHandler, '(\d+)', 'class', 'method', [], ['args_captured' => ['arg1']] ))->matches( $mockRequest, $matchedValues ));        
  }
  
  
  public function testExecuteCallsHandler() : void
  {
    $mockHandler = $this->getMockBuilder( IRouteHandler::class )->getMock();
    $mockHandler->method( 'execute' )
      ->with( $this->isType( 'string' ),$this->isType( 'string' ), $this->isType( 'array' ), $this->isType( 'array' ))
      ->will( $this->returnCallback( function( $class, $method, $args, $context ) {
        return [$class,$method,$args,$context];
      }));
    
    $args = ['arg1' => true];
    $expected = ['class','method',$args,[]];
    $this->assertSame( $expected, ( new ClassHTTPRoute( $mockHandler, 'test', 'class', 'method', [], [] ))->execute( $args ));    
  }
  
  
  public function testGetContext() : void
  {
    $context = ['contextkey' => 'contextvalue'];
    $mockHandler = $this->getMockBuilder( IRouteHandler::class )->getMock();
    $instance = new ClassHTTPRoute( $mockHandler, 'test', 'class', 'method', [], $context );
    $this->assertSame( $context, $instance->getContext());
  }

  
  public function testGetOptions() : void
  {
    $options = ['optionkey' => 'optionvalue'];
    $mockHandler = $this->getMockBuilder( IRouteHandler::class )->getMock();
    $instance = new ClassHTTPRoute( $mockHandler, 'test', 'class', 'method', $options, [] );
    $this->assertSame( $options, $instance->getOptions());
  }  
}
