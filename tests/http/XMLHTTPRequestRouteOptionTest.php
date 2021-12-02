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
use buffalokiwi\telephonist\http\XMLHTTPRequestRouteOption;
use PHPUnit\Framework\TestCase;




class XMLHTTPRequestRouteOptionTest extends TestCase
{
  public function testCommandReturnsCommandFromConstructor() : void
  {
    $c = new XMLHTTPRequestRouteOption( XMLHTTPRequestRouteOption::COMMAND );
    $this->assertSame( [XMLHTTPRequestRouteOption::COMMAND], $c->getCommand());
  }
  
  
  public function testValidateReturnsTrueWhenXHRHeaderIsPresent() : void
  {
    $mockRequest = $this->getMockBuilder( IHTTPRouteRequest::class )->getMock();
    $mockRequest->method( 'getHeader' )
      ->with( $this->isType( 'string' ))
      ->will( $this->returnCallback( function( $header ) {
        return ( $header == 'HTTP_X_REQUESTED_WITH' ) ? 'XMLHTTPRequest' : '';
      }
    ));
    
    $mockRoute = $this->getMockBuilder( IHTTPRoute::class )->getMock();
    $mockRoute->method( 'getOptions' )->willReturn( [XMLHTTPRequestRouteOption::COMMAND] );
    
    $c = new XMLHTTPRequestRouteOption( XMLHTTPRequestRouteOption::COMMAND );
    
    
    $this->assertTrue( $c->validate( $mockRequest, $mockRoute ));   
  }  
}
