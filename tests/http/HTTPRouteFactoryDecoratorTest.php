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

use PHPUnit\Framework\TestCase;

class HTTPRouteFactoryDecoratorTest extends TestCase
{
  
  /**
   * Tests that the constructor accepts a single instance of factory 
   * @return void
   */
  public function testConstructor() : void
  {
    $mockFactory = $this->getMockBuilder( \buffalokiwi\telephonist\http\IHTTPRouteFactory::class )->getMock();
    
    new \buffalokiwi\telephonist\http\HTTPRouteFactoryDecorator( $mockFactory );
    
    $this->expectError( \ArgumentCountError::class );
    new \buffalokiwi\telephonist\http\HTTPRouteFactoryDecorator();
  }
  
  
  /**
   * Test that calling getPossibleRoutes() calls the getPossibleRoutes() method of the factory supplied to the constructor
   * @return void
   */
  public function testGetPossibleRoutes() : void
  {
    $mockRequest = $this->getMockBuilder( \buffalokiwi\telephonist\http\IHTTPRouteRequest::class )->getMock();
    $mockFactory = $this->getMockBuilder( \buffalokiwi\telephonist\http\IHTTPRouteFactory::class )->getMock();
    
    $mockRoute1 = $this->getMockBuilder( buffalokiwi\telephonist\http\IHTTPRoute::class )->getMock();
    $mockRoute2 = $this->getMockBuilder( buffalokiwi\telephonist\http\IHTTPRoute::class )->getMock();
    
    $routes = [$mockRoute1, $mockRoute2];
    
    $mockFactory->method( 'getPossibleRoutes' )->will( $this->generate( $routes ));
    
    $c = new \buffalokiwi\telephonist\http\HTTPRouteFactoryDecorator( $mockFactory );
    
    $yres = $c->getPossibleRoutes( $mockRequest );
    
    $this->assertInstanceOf( Generator::class, $yres );
    
    
    $res = [];
    foreach( $yres as $y )
    {
      $res[] = $y;
    }    
    
    $this->assertEquals( 2, sizeof( $res ));
    
    list( $a, $b ) = array_values( $res );
    
    $this->assertTrue( $mockRoute1 === $a || $mockRoute1 === $b );
    $this->assertTrue( $mockRoute2 === $a || $mockRoute2 === $b );
  }
  

  private function generate( array $y )
  {
    return $this->returnCallback( function() use ( $y ) 
    {
      foreach ( $y as $v )
      {
        yield $v;
      }
    });
  }  
}
