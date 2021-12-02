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

use buffalokiwi\telephonist\http\DefaultHTTPRouteRequest;
use PHPUnit\Framework\TestCase;


class DefaultHTTPRouteRequestTest extends TestCase
{
  /**
   * Tests that if the array supplied to the constructor contains the 
   * key "REQUEST_URI", that the value associated with that key is returned, or if
   * they key does not exist, then an InvalidArgumentException is thrown 
   * 
   * @return void
   */
  public function testGetURI() : void
  {
    $a = ['REQUEST_URI' => 'test'];
    $c = new DefaultHTTPRouteRequest( $a );
    $this->assertSame( 'test', $c->getURI());
    
    $a = [];
    $this->expectException( \InvalidArgumentException::class );
    $c = new DefaultHTTPRouteRequest( $a );
  }
  
  
  /**
   * Tests that the same array passed to the constructor is returned by getHeaders()
   * @return void
   */
  public function testGetHeaders() : void
  {
    $a = ['REQUEST_URI' => 'test'];
    $c = new DefaultHTTPRouteRequest( $a );
    $this->assertSame( $a, $c->getHeaders());    
  }
  
  
  /**
   * Test that if getHeader() either returns the value associated with some existing 
   * key passed to the constructor or that getHeader() returns empty string for non-existing keys.
   * @return void
   */
  public function testGetHeader() : void
  {
    $a = ['REQUEST_URI' => 'test'];
    $c = new DefaultHTTPRouteRequest( $a );
    $this->assertSame( 'test', $c->getHeader( 'REQUEST_URI' ));    
    $this->assertSame( '', $c->getHeader( 'request_uri' ));   
  }
}
