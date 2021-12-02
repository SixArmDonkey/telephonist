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

use buffalokiwi\telephonist\http\DefaultHTTPRouteOptions;
use buffalokiwi\telephonist\http\IHTTPRouteOption;
use PHPUnit\Framework\TestCase;

class DefaultHTTPRouteOptionsTest extends TestCase
{
  /**
   * Tests that the constructor:
   * 
   * 1) accepts multiple options 
   * 2) accepts multiple options with the same command 
   * 
   * @return void
   */
  public function testConstructor() : void
  {
    $opt1 = $this->getMockBuilder( IHTTPRouteOption::class )->getMock();
    $opt1->method( 'getCommand' )->willReturn( ['A'] );
    
    $opt1a = $this->getMockBuilder( IHTTPRouteOption::class )->getMock();
    $opt1a->method( 'getCommand' )->willReturn( ['A'] );
    
    $opt2 = $this->getMockBuilder( IHTTPRouteOption::class )->getMock();
    $opt2->method( 'getCommand' )->willReturn( ['B'] );
    
    
    new DefaultHTTPRouteOptions( $opt1 );
    new DefaultHTTPRouteOptions( $opt1, $opt2 );
    new DefaultHTTPRouteOptions( $opt1, $opt2, $opt1a );
    
    $this->expectNotToPerformAssertions();
  }
  
  
  /**
   * Test getOptions()
   * 
   * 1) Test that the same objects passed to the constructor are returned by getOptions()
   * 2) Test that when passing multiple options to the constructor with the same command, getOptions() returns 
   * all of the commands
   * @return void
   */
  public function testGetOptions() : void
  {
    $opt1 = $this->getMockBuilder( IHTTPRouteOption::class )->getMock();
    $opt1->method( 'getCommand' )->willReturn( ['A'] );
    
    $opt1a = $this->getMockBuilder( IHTTPRouteOption::class )->getMock();
    $opt1a->method( 'getCommand' )->willReturn( ['A'] );
    
    $opt2 = $this->getMockBuilder( IHTTPRouteOption::class )->getMock();
    $opt2->method( 'getCommand' )->willReturn( ['B'] );
    
    $opts = new DefaultHTTPRouteOptions( $opt1, $opt2, $opt1a );
    
    $this->assertSame( 3, sizeof( $opts->getOptions()));
    
    //..This is stupid.
    foreach( $opts->getOptions() as $o )
    {
      $this->assertTrue( $o === $opt1 || $o === $opt1a || $o === $opt2 );
    }
  }
}