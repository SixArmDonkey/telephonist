# BuffaloKiwi Telephonist

Telephonist is a simple PHP router library, which is a program for mapping http requests to handlers based on various
matching criteria.

Table of Contents 

1. [What's in the box?](#interfaces-and-implementations]
2. Basic Routing
3. 



## Interfaces and Implementations


### Interfaces 


1. **Handlers**
    1. IArgumentResolver - Using the PHP Reflection API, determine the type, number and values of arguments used when invoking some method  
    2. IRouteHandler - Route handlers are responsible for locating some endpoint and returning the content.  This can be anything, a file, class, some global function, an RPC, etc.  
2. **Route Objects**
    1. IHTTPRoute - An object representing a potential destination and the requirements for connection.  
    2. IHTTPRouteFactory - A factory optionally used to supply instances of IHTTPRoute to implementations of IHTTPRouter  
3. **Route Options**
    1. IHTTPRouteOption - Used to extend the conditions required for route matching.  This can be anything, such as the HTTP Method, accept headers, authentication tokens, etc.  
    2. IHTTPRouteOptions - A collection of IHTTPRouteOption  
4. **Request for Routing**
    1. IHTTPRouteRequest - Represents a HTTP Request message and exposes the relevant parts to the router  
5. **Router**
    1. IHTTPRouter - The program responsible for determining which route to invoke based on some client request   


### Implementations

1. **Handlers**
ArgumentResolver
ClassRouteHandler
FunctionRouteHandler

2. **Route Objects**
ArrayRouteFactory
HTTPRoute 
ClassHTTPRoute
DefaultHTTPRoute
DefaultHTTPRouteFactory
NestedArrayRouteFactory
HTTPRouteFactoryGroup

3. **Route Options**
DefaultHTTPRouteOptions
HTTPRouteOption
MethodRouteOption
XMLHTTPRequestRouteOption

4. **Request for Routing**
DefaultHTTPRouteRequest 

5. **Router**
BasicRouter
DefaultHTTPRouter
  







## The most basic router possible

```php
try {
  echo ( new BasicRouter([
    '/' => fn() => 'This is the home page'
  ]))->route( new DefaultHTTPRouteRequest( $_SERVER ));
} catch( RouteNotFoundException $e ) {
  http_response_code( $e->getCode());  
}    
```

http://localhost displays: "This is the home page"


## Adding arguments

Arguments can be added by using standard capture groups 


```php
try {
  echo ( new BasicRouter([
    '(\d+)' => fn( int $id ) => 'Found digit ' . $id
  ]))->route( new DefaultHTTPRouteRequest( $_SERVER ));
} catch( RouteNotFoundException $e ) {
  http_response_code( $e->getCode());  
}    
```
http://localhost/1 displays: "Found digit 1"


**We can used named arguments by using named capture groups like this**

If named arguments are used, then ALL arguments must be named.  Mixing of positional arguments with named arguments is not allowed.

In the following example, naming $id anything other than $id will throw an exception.

```php
try {
  echo ( new BasicRouter([
    '(?<id>\d+)' => fn( int $id ) => 'Found digit ' . $id,
  ]))->route( new DefaultHTTPRouteRequest( $_SERVER ));
} catch( RouteNotFoundException $e ) {
  http_response_code( $e->getCode());  
}
```


    




```php

use buffalokiwi\telephonist\DefaultRouteConfig;
use buffalokiwi\telephonist\http\DefaultHTTPRouteOptions;
use buffalokiwi\telephonist\http\DefaultHTTPRouter;
use buffalokiwi\telephonist\http\DefaultHTTPRouteRequest;
use buffalokiwi\telephonist\http\ArrayRouteFactory;
use buffalokiwi\telephonist\http\DefaultRouteFactory;
use buffalokiwi\telephonist\http\MethodRouteOption;
use buffalokiwi\telephonist\http\XMLHTTPRequestRouteOption;
use buffalokiwi\telephonist\RouteNotFoundException;
use buffalokiwi\telephonist\http\HTTPRouteFactoryGroup;

class LocalRouterTest
{
  public const ROUTE_CONFIG = [
    'test' => [LocalRouterTest::class, 'helloRouter', ['GET'], []],
    'test/(\d+)' => [LocalRouterTest::class, 'helloRouterArg', ['GET'], []]
  ];

  public static function helloRouter() : string
  {
    return 'Hello Router!';
  }
  
  
  public static function helloRouterArg( int $i ) : string
  {
    return 'Hello Router ' . (string)$i . '!';
  }
}

$router = new DefaultHTTPRouter(
  new HTTPRouteFactoryGroup(
    new ArrayRouteFactory(
      new DefaultRouteConfig( fn() => LocalRouterTest::ROUTE_CONFIG )),
    (new DefaultRouteFactory())
    ->add( 'test2', function() {
      return 'Hello Router 2!';
    })
    ->add( 'test2/(\d+)', function( int $int, array $context ) {
      return 'Found ' . (string)$int;
    }, ['GET'], ['context' => 'foo'] )
  ),
  new DefaultHTTPRouteOptions(
    new MethodRouteOption(),
    new XMLHTTPRequestRouteOption()
));


try {
  echo $router->route( new DefaultHTTPRouteRequest( $_SERVER ));
} catch( RouteNotFoundException $e ) {
  http_response_code( $e->getCode());  
  echo 'Not found';
}
```
