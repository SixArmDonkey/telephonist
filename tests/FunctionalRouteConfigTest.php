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

use buffalokiwi\telephonist\FunctionalRouteConfig;
use PHPUnit\Framework\TestCase;

class FunctionalRouteConfigTest extends TestCase 
{
  public function testConstruct()
  {
    //..Expect nothing to happen
    new FunctionalRouteConfig( function() {} );
    
    $this->expectError( TypeError::class );    
    new FunctionalRouteConfig();
  }
  
  
  /**
   * Tests that passing a closure to the constructor that returns an array returns the same array via getConfig().
   * That was a horrible description.
   * 
   * Test that returning anything other than an array from the closure throws a RouteConfigurationException
   * 
   * @return void
   */
  public function testGetConfig() : void
  {
    $a = ['test'];
    $f = function() use($a) { return $a; } ;
    
    $c = new FunctionalRouteConfig( $f );    
    $this->assertSame( $a, $c->getConfig());
    
    $err = 'When returning anything other than array from the closure passed to the constructor, a RouteConfigurationException must be thrown';
    
    
    try {
      ( new FunctionalRouteConfig( function() {} ))->getConfig();
      $this->fail( $err );
    } catch ( buffalokiwi\telephonist\RouteConfigurationException ) {
      //..Expected
    }
    
    try {
      ( new FunctionalRouteConfig( function() { return 'a'; } ))->getConfig();
      $this->fail( $err );
    } catch ( buffalokiwi\telephonist\RouteConfigurationException ) {
      //..Expected
    }
    
    try {
      ( new FunctionalRouteConfig( function() { return 1; } ))->getConfig();
      $this->fail( $err );
    } catch ( buffalokiwi\telephonist\RouteConfigurationException ) {
      //..Expected
    }
    
    try {
      ( new FunctionalRouteConfig( function() { return 1.1; } ))->getConfig();
      $this->fail( $err );
    } catch ( buffalokiwi\telephonist\RouteConfigurationException ) {
      //..Expected
    }
    
    try {
      ( new FunctionalRouteConfig( function() { return true; } ))->getConfig();
      $this->fail( $err );
    } catch ( buffalokiwi\telephonist\RouteConfigurationException ) {
      //..Expected
    }
    
    try {
      ( new FunctionalRouteConfig( function() { return new \stdClass(); } ))->getConfig();
      $this->fail( $err );
    } catch ( buffalokiwi\telephonist\RouteConfigurationException ) {
      //..Expected
    }
    
    try {
      ( new FunctionalRouteConfig( function() { return null; } ))->getConfig();
      $this->fail( $err );
    } catch ( buffalokiwi\telephonist\RouteConfigurationException ) {
      //..Expected
    }
  }
}
