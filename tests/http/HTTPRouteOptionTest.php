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


use buffalokiwi\telephonist\http\HTTPRouteOption;
use PHPUnit\Framework\TestCase;


class HTTPRouteOptionTest extends TestCase
{
  /**
   * Test that passing an empty string or passing a non-alphanumeric string to the constructor 
   * throws an \InvalidArgumentException
   * @return void
   */
  public function testConstructor() : void
  {
    
    try {
      $stub = $this->getMockForAbstractClass( HTTPRouteOption::class, [''] );
      $this->fail( HTTPRouteOption::class . '::__construct() must throw an exeption when $command is empty' );
    } catch ( InvalidArgumentException ) {
      //..Expected
    }

    
    try {
      $stub = $this->getMockForAbstractClass( HTTPRouteOption::class, ['%!@#'] );
      $this->fail( HTTPRouteOption::class . '::__construct() must throw an exeption when $command is not alphanumeric' );
    } catch ( InvalidArgumentException ) {
      //..Expected
    }
    
    
    $stub = $this->getMockForAbstractClass( HTTPRouteOption::class, ['test1'] );
    $this->assertSame( ['test1'], $stub->getCommand());
  }
}

