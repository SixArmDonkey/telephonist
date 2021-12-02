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

use buffalokiwi\telephonist\http\IHTTPRoute;
use buffalokiwi\telephonist\http\IHTTPRouteRequest;
use buffalokiwi\telephonist\http\MethodRouteOption;
use buffalokiwi\telephonist\RouteConfigurationException;
use PHPUnit\Framework\TestCase;


class MethodRouteOptionTest extends TestCase
{
  public function testDefaultConstructorIsValidAndMethodsAreReturnedAsCommand() : void
  {
    $this->assertSame( MethodRouteOption::VALID, ( new MethodRouteOption())->getCommand());    
  }
  
  
  public function testInvalidMethodInConstructorThrowsException() : void
  {
    $this->expectException( RouteConfigurationException::class );
    ( new MethodRouteOption( 'Invalid' ));   
  }

  
  public function testDefaultValidateReturnsTrueWhenRequestMethodIsValid() : void
  {
    $c = new MethodRouteOption();
    $this->assertTrue( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::GET )));
    
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::POST )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::PUT )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::PATCH )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::DELETE )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::OPTIONS )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::HEAD )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::POST ), $this->getMockRoute( MethodRouteOption::GET )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::PUT ), $this->getMockRoute( MethodRouteOption::GET )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::PATCH ), $this->getMockRoute( MethodRouteOption::GET )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::DELETE ), $this->getMockRoute( MethodRouteOption::GET )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::HEAD ), $this->getMockRoute( MethodRouteOption::GET )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::OPTIONS ), $this->getMockRoute( MethodRouteOption::GET )));
  }
  
  
  public function testRestrictedConstructorAndValidate() : void
  {
    $c = new MethodRouteOption();
    $this->assertTrue( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::GET )));
    $this->assertTrue( $c->validate( $this->getMockRequest( MethodRouteOption::POST ), $this->getMockRoute( MethodRouteOption::POST )));
    $this->assertTrue( $c->validate( $this->getMockRequest( MethodRouteOption::PATCH ), $this->getMockRoute( MethodRouteOption::PATCH )));
    
    $c = new MethodRouteOption( MethodRouteOption::GET, MethodRouteOption::POST );
    $this->assertTrue( $c->validate( $this->getMockRequest( MethodRouteOption::GET ), $this->getMockRoute( MethodRouteOption::GET )));
    $this->assertTrue( $c->validate( $this->getMockRequest( MethodRouteOption::POST ), $this->getMockRoute( MethodRouteOption::POST )));
    $this->assertFalse( $c->validate( $this->getMockRequest( MethodRouteOption::PATCH ), $this->getMockRoute( MethodRouteOption::PATCH )));
  }
  
  
  private function getMockRequest( string $method ) : IHTTPRouteRequest 
  {
    $mockRequest = $this->getMockBuilder( IHTTPRouteRequest::class )->getMock();
    $mockRequest->method( 'getHeader' )
      ->with( $this->isType( 'string' ))
      ->will( $this->returnCallback( function( $header ) use($method) {
        return ( $header == 'REQUEST_METHOD' ) ? $method : '';
      }
    ));
    
    return $mockRequest;
  }    
  
  
  private function getMockRoute( string $method ) : IHTTPRoute
  {
    $mockRoute = $this->getMockBuilder( IHTTPRoute::class )->getMock();
    $mockRoute->method( 'getOptions' )->willReturn( [$method] );
    return $mockRoute;
  }
}
