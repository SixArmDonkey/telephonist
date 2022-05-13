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

use buffalokiwi\telephonist\http\ArrayRouteFactory;
use buffalokiwi\telephonist\http\IHTTPRoute;
use buffalokiwi\telephonist\http\IHTTPRouteRequest;
use buffalokiwi\telephonist\IRouteConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class ArrayRouteFactoryTest extends TestCase 
{
  private const PATH1 = ['path1class', 'path1method', ['path1option1'], ['path1contextkey' => 'path1contextvalue']];
  private const TEST1 = ['path1' => self::PATH1];
 
  
  public function testClosureIsCalledWhenCreatingRoute() : void
  {
    $mockConfig = $this->getMockBuilder( IRouteConfig::class )->getMock();
    $mockConfig->method( 'getConfig' )->willReturn( self::TEST1 );
    
    $phpunit = $this;
    
    foreach(( new ArrayRouteFactory( 
      $mockConfig, 
      function( string $path, string $class, string $method, array $options, array $context ) use ($phpunit) : IHTTPRoute {
        
        $phpunit->assertSame( 'path1', $path );
        $phpunit->assertSame( 'path1class', $class );
        $phpunit->assertSame( 'path1method', $method );
        $phpunit->assertCount( 1, $options );
        $phpunit->assertTrue( isset( $options[0] ));
        $phpunit->assertSame( 'path1option1', $options[0] );
        $phpunit->assertCount( 1, $context );
        $phpunit->assertTrue( isset( $context['path1contextkey'] ));
        $phpunit->assertSame( 'path1contextvalue', $context['path1contextkey'] );
        
        return $phpunit->getMockBuilder( IHTTPRoute::class )->getMock();
      }
    ))->getPossibleRoutes( $this->getMockRequest( '' )) as $g )
    {
      //..do nothing, this is tested inside of the above function 
    }
  }
  
  
  private function getMockRequest( string $uri ) : MockObject
  {
    $mock = $this->getMockBuilder( IHTTPRouteRequest::class )->getMock();
    $mock->method( 'getURI' )->willReturn( $uri );
    return $mock;
  }
}
