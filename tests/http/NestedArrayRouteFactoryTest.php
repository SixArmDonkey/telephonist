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


namespace NestedARrayRouteFactoryTest;

use buffalokiwi\telephonist\handler\ArgumentResolver;
use buffalokiwi\telephonist\handler\ClassRouteHandler;
use buffalokiwi\telephonist\http\ArrayRouteFactory;
use buffalokiwi\telephonist\http\DefaultHTTPRoute;
use buffalokiwi\telephonist\http\DefaultHTTPRouteRequest;
use buffalokiwi\telephonist\http\IHTTPRoute;
use buffalokiwi\telephonist\http\IHTTPRouteRequest;
use buffalokiwi\telephonist\http\NestedArrayRouteFactory;
use buffalokiwi\telephonist\IRouteConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;



interface TestIHTTPRoute extends IHTTPRoute
{
  public function __getTestData() : array;
}


class NestedArrayRouteFactoryTest extends TestCase 
{
  private const PATH1 = ['path1class', 'path1method', ['path1option1'], ['path1contextkey' => 'path1contextvalue']];
  private const PATH2 = ['path2class', 'path2method', ['path2option1'], ['path2contextkey' => 'path2contextvalue']];
  private const PATH3 = ['path3class', 'path3method', ['path3option1'], ['path3contextkey' => 'path3contextvalue']];
  private const PATH4 = ['path4class', 'path4method', ['path4option1'], ['path4contextkey' => 'path4contextvalue']];
  private const PATH5 = ['path5class', 'path5method', ['path5option1'], ['path5contextkey' => 'path5contextvalue']];
  private const PATH6 = ['path6class', 'path6method', ['path6option1'], ['path6contextkey' => 'path6contextvalue']];
  
  private const TEST1 = ['path1' => self::PATH1];
  private const TEST2 = [
    'path1' => self::PATH1,
    'path2' => self::PATH2    
  ];
  
  private const TEST3 = ['path3\-with\-argument/(\d+)' => self::PATH3];
  
  
  private const TEST4 = [
    'path4' => [
      '(\d+)' => self::PATH4
     ],      
  ];
  
  private const TEST5 = [
    'path5' => [
      '(\d+)' => [
        self::PATH5,
        self::PATH1
      ],
    ],
  ];
  
  
  private const TEST6 = [
    'path6' => [
      '(\d+)' => self::PATH6,
      '' => self::PATH1
    ]
  ];
  
  
  private const TEST7 = [
    'path1' => self::PATH1,
    'path2' => self::PATH2,
    'path3\-with\-argument/(\d+)' => self::PATH3,
    'path4' => [
      '(\d+)' => self::PATH4
     ],      
    'path5' => [
      '(\d+)' => [
        self::PATH5,
        self::PATH1
      ],
    ],
    'path6' => [
      '(\d+)' => self::PATH6,
      '' => self::PATH1
    ]
  ];
  
  
  private function getInstance( array $config ) : MockObject
  {
    $mockConfig = $this->getMockBuilder( IRouteConfig::class )->getMock();
    $mockConfig->method( 'getConfig' )->willReturn( $config );
    
    $mockRoute = $this->getMockBuilder( TestIHTTPRoute::class )->getMock();
    
    $mock = $this->getMockForAbstractClass( NestedArrayRouteFactory::class, [$mockConfig] );
    
    $mock->method( 'createRoute' )
      ->with( 
        $this->isType( 'string' ), 
        $this->isType( 'string' ), 
        $this->isType( 'string' ), 
        $this->isType( 'array'  ), 
        $this->isType( 'array'  ))
      ->will( $this->returnCallback( function( $path, $class, $method, $args, $context ) use ($mockRoute) {
        $mr = clone $mockRoute;
        $mr->method( '__getTestData' )->willReturn( [$path, $class, $method, $args, $context] );
        return $mr;
      }
    ));
    
    return $mock;
  }
  
  
  private function getMockRequest( string $uri ) : MockObject
  {
    $mock = $this->getMockBuilder( IHTTPRouteRequest::class )->getMock();
    $mock->method( 'getURI' )->willReturn( $uri );
    return $mock;
  }
  
  
  private function assertPath1( string $path, array $data ) : void
  {    
    $this->assertCount( 5, $data );
    $this->assertSame( $path, $data[0] );
    $this->assertSame( 'path1class', $data[1] );
    $this->assertSame( 'path1method', $data[2] );
    $this->assertIsArray( $data[3] );
    $this->assertCount( 1, $data[3] );
    $this->assertSame( 'path1option1', reset( $data[3] ));
    $this->assertIsArray( $data[4] );
    $this->assertTrue( isset( $data[4]['path1contextkey'] ));
    $this->assertSame( 'path1contextvalue', $data[4]['path1contextkey'] );
  }
  
  
  private function assertPath2( string $path, array $data ) : void
  {
    $this->assertCount( 5, $data );
    $this->assertSame( $path, $data[0] );
    $this->assertSame( 'path2class', $data[1] );
    $this->assertSame( 'path2method', $data[2] );
    $this->assertIsArray( $data[3] );
    $this->assertCount( 1, $data[3] );
    $this->assertSame( 'path2option1', reset( $data[3] ));
    $this->assertIsArray( $data[4] );
    $this->assertTrue( isset( $data[4]['path2contextkey'] ));
    $this->assertSame( 'path2contextvalue', $data[4]['path2contextkey'] );    
  }
  
  
  private function assertPath3( string $path, array $data ) : void
  {
    $this->assertCount( 5, $data );
    $this->assertSame( $path, $data[0] );
    $this->assertSame( 'path3class', $data[1] );
    $this->assertSame( 'path3method', $data[2] );
    $this->assertIsArray( $data[3] );
    $this->assertCount( 1, $data[3] );
    $this->assertSame( 'path3option1', reset( $data[3] ));
    $this->assertIsArray( $data[4] );
    $this->assertTrue( isset( $data[4]['path3contextkey'] ));
    $this->assertSame( 'path3contextvalue', $data[4]['path3contextkey'] );    
  }


  private function assertPath4( string $path, array $data ) : void
  {
    $this->assertCount( 5, $data );
    $this->assertSame( $path, $data[0] );
    $this->assertSame( 'path4class', $data[1] );
    $this->assertSame( 'path4method', $data[2] );
    $this->assertIsArray( $data[3] );
    $this->assertCount( 1, $data[3] );
    $this->assertSame( 'path4option1', reset( $data[3] ));
    $this->assertIsArray( $data[4] );
    $this->assertTrue( isset( $data[4]['path4contextkey'] ));
    $this->assertSame( 'path4contextvalue', $data[4]['path4contextkey'] );    
  }


  private function assertPath5( string $path, array $data ) : void
  {
    $this->assertCount( 5, $data );
    $this->assertSame( $path, $data[0] );
    $this->assertSame( 'path5class', $data[1] );
    $this->assertSame( 'path5method', $data[2] );
    $this->assertIsArray( $data[3] );
    $this->assertCount( 1, $data[3] );
    $this->assertSame( 'path5option1', reset( $data[3] ));
    $this->assertIsArray( $data[4] );
    $this->assertTrue( isset( $data[4]['path5contextkey'] ));
    $this->assertSame( 'path5contextvalue', $data[4]['path5contextkey'] );    
  }
  
  
  private function assertPath6( string $path, array $data ) : void
  {
    $this->assertCount( 5, $data );
    $this->assertSame( $path, $data[0] );
    $this->assertSame( 'path6class', $data[1] );
    $this->assertSame( 'path6method', $data[2] );
    $this->assertIsArray( $data[3] );
    $this->assertCount( 1, $data[3] );
    $this->assertSame( 'path6option1', reset( $data[3] ));
    $this->assertIsArray( $data[4] );
    $this->assertTrue( isset( $data[4]['path6contextkey'] ));
    $this->assertSame( 'path6contextvalue', $data[4]['path6contextkey'] );    
  }

  
  public function testSingleRoute() : void
  {
    $c = $this->getInstance( self::TEST1 );
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( '' ));
    
    $this->assertCount( 1, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    $this->assertPath1( 'path1', $res[0]->__getTestData());
  }
  
  
  public function testTwoRoutes() : void
  {
    $c = $this->getInstance( self::TEST2 );
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( '' ));
    
    $this->assertCount( 2, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertTrue( isset( $res[1] ));
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    
    $this->assertPath1( 'path1', $res[0]->__getTestData());
    $this->assertPath2( 'path2', $res[1]->__getTestData());
  }
  
  
  public function testRouteWithSlashCreatesTwoBucketsAndReturnsData() : void
  {
    $c = $this->getInstance( self::TEST3 );
    
    $this->assertCount( 0, $c->getPossibleRoutes( $this->getMockRequest( '' )));
    $this->assertCount( 0, $c->getPossibleRoutes( $this->getMockRequest( '/' )));
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( 'a/b' ));

    $this->assertCount( 1, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    $this->assertPath3( 'path3\-with\-argument/(\d+)', $res[0]->__getTestData());    
  }
  
  
  public function testRouteWithSlashCreatesTwoBucketsAndReturnsDataNestedArrayConfiguration() : void
  {
    $c = $this->getInstance( self::TEST4 );
    
    $this->assertCount( 0, $c->getPossibleRoutes( $this->getMockRequest( '' )));
    $this->assertCount( 0, $c->getPossibleRoutes( $this->getMockRequest( '/' )));
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( 'a/b' ));

    $this->assertCount( 1, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    $this->assertPath4( 'path4/(\d+)', $res[0]->__getTestData());    
  }
  
  
  
  public function testMultiRouteNestedArrayConfiguration() : void
  {
    $c = $this->getInstance( self::TEST5 );
    
    
    $this->assertCount( 0, $c->getPossibleRoutes( $this->getMockRequest( '' )));
    $this->assertCount( 0, $c->getPossibleRoutes( $this->getMockRequest( '/' )));
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( 'a/b' ));
    

    $this->assertCount( 2, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertTrue( isset( $res[1] ));
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[1] );
    $this->assertPath5( 'path5/(\d+)', $res[0]->__getTestData());    
    $this->assertPath1( 'path5/(\d+)', $res[1]->__getTestData());
  }
  
  
  public function testNestedArrayMultiRouteWithEmptyStringForDefault() : void
  {
    $c = $this->getInstance( self::TEST6 );
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( '' ));
    $this->assertCount( 1, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    $this->assertPath1( 'path6', $res[0]->__getTestData());    
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( 'a/b' ));
    $this->assertCount( 2, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertTrue( isset( $res[1] ));
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[1] );
    $this->assertPath6( 'path6/(\d+)', $res[0]->__getTestData());    
    $this->assertPath1( 'path6', $res[1]->__getTestData());    
  }
  
  
  public function testEverything() : void
  {
    $c = $this->getInstance( self::TEST7 );
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( '' ));
    
    $this->assertCount( 3, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertTrue( isset( $res[1] ));
    $this->assertTrue( isset( $res[2] ));    
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[1] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[2] );
    $this->assertPath1( 'path1', $res[0]->__getTestData());
    $this->assertPath2( 'path2', $res[1]->__getTestData());
    $this->assertPath1( 'path6', $res[2]->__getTestData());
    
    
    $res = $c->getPossibleRoutes( $this->getMockRequest( 'a/b' ));
    $this->assertCount( 8, $res );
    $this->assertTrue( isset( $res[0] ));
    $this->assertTrue( isset( $res[1] ));
    $this->assertTrue( isset( $res[2] ));    
    $this->assertTrue( isset( $res[3] ));
    $this->assertTrue( isset( $res[4] ));
    $this->assertTrue( isset( $res[5] ));    
    $this->assertTrue( isset( $res[6] ));
    $this->assertTrue( isset( $res[7] ));    
    $this->assertInstanceOf( IHTTPRoute::class, $res[0] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[1] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[2] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[3] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[4] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[5] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[6] );
    $this->assertInstanceOf( IHTTPRoute::class, $res[7] );
    
    $this->assertPath3( 'path3\-with\-argument/(\d+)', $res[0]->__getTestData());
    $this->assertPath4( 'path4/(\d+)', $res[1]->__getTestData());
    $this->assertPath5( 'path5/(\d+)', $res[2]->__getTestData());
    $this->assertPath1( 'path5/(\d+)', $res[3]->__getTestData());
    $this->assertPath6( 'path6/(\d+)', $res[4]->__getTestData());
    $this->assertPath1( 'path1', $res[5]->__getTestData());
    $this->assertPath2( 'path2', $res[6]->__getTestData());
    $this->assertPath1( 'path6', $res[7]->__getTestData());
    
    $this->assertCount( 8, $c->getPossibleRoutes( $this->getMockRequest( 'a/b/c' )));
  }
  
  
  public function things() : void
  {
    $config = $this->getMockBuilder( IRouteConfig::class )->getMock();
    $config->method( 'getConfig' )->willReturn([
      'path1' => [RouteEndpoint::class, 'staticRoute', [], []],
      'path2' => ['class' => 'class_name', 'method' => 'method_name', 'options' => ['opt1', 'opt2'], 'context' => ['context_array']],

      'path3\-with\-argument/(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']],

      'path4\-nested' => [
        '(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']]
       ],

      'path5' => [
        '(\d+)' => [
          ['class_name', 'method_name', ['method' => 'get'], ['context_array']],
          ['class_name', 'method_name', ['method' => 'post'], ['context_array']]
        ],
      ],

      'path6' => [
        '(\d+)' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']],
        '' => ['class_name', 'method_name', ['opt1','opt2'], ['context_array']]
      ]
    ]
  );
    
    
    $handler = new ClassRouteHandler( new ArgumentResolver());
    $f = new ArrayRouteFactory(
      $config,
      function( string $path, string $class, string $method, array $options, array $context ) use ($handler) : IHTTPRoute {
        //..Different handlers are applied here.  This should probably be based on some context array entry.
        //..ie: class, procedural, etc.
        return new DefaultHTTPRoute( $handler, $path, $class, $method, $options, $context );
      }
    );
    
    $_SERVER['REQUEST_URI'] = '/path1';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    $request = new DefaultHTTPRouteRequest( $_SERVER );
    $matchedValues = [];
    
    foreach( $f->getPossibleRoutes( $request ) as $route )
    {
      if ( $route->matches( $request, $matchedValues ))
      {
        echo $route->execute( $matchedValues );
        
      }
    }
    
    echo 'not found';
  }
}
