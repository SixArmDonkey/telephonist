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

namespace buffalokiwi\telephonist\http;

use InvalidArgumentException;


/**
 * The default list of route options.
 * 
 * If multiple route options with the same command are supplied, this will simply execute them in order.
 * 
 */
class DefaultHTTPRouteOptions implements IHTTPRouteOptions
{
  /**
   * A map of available options.
   * 
   * [command => [IHTTPRouteOption[]];
   * 
   * @var IHTTPRouteOption[] 
   */
  private array $options = [];
  
  /**
   * Complete list of options 
   * @var array
   */
  private array $masterList = [];
  
  
  /**
   * @param IHTTPRouteOption $options Options 
   */
  public function __construct( IHTTPRouteOption ...$options )
  {
    foreach( $options as $o ) 
    {
      foreach( $o->getCommand() as $c )
      {
        if ( !isset( $this->options[$c] ))
          $this->options[$c] = [];

        $this->options[$c][] = $o;
        $this->masterList[] = $o;
      }
    }
  }
  
  
  /**
   * Retrieve a list of enabled options
   * @param string $option Zero or more options.  Passing zero returns all options.
   * @return IHTTPRouteOption[]
   * @throws \InvalidArgumentException if the specified option does not exist 
   */
  public function getOptions( string ...$option ) : array
  {
    if ( empty( $option ))
      return $this->masterList;
    
    
    $out = [];
    foreach( $option as $o )
    {
      if ( empty( $o ))
        throw new InvalidArgumentException( 'option must not be empty' );
      else if ( !isset( $this->options[$o] ))
        throw new InvalidArgumentException( 'The specified option "' . $o . '" does not exist' );
      
      foreach( $this->options[$o] as $o1 )
      {
        $out[] = $o1;
      }
    }
    
    return $out;
  }
  
  
  /**
   * Test if all of the supplied options are set 
   * @param string $option options list
   * @return bool
   * @throws InvalidArgumentException if any trimmed $option is an empty string 
   */
  public function hasOption( string ...$option ) : bool{
    foreach( $option as $o )
    {
      if ( empty( $o ))
        throw new InvalidArgumentException( 'option must not be empty' );
      else if ( !isset( $this->options[$o] ))
        return false;
    }
    
    return true;
  }
  
  
  /**
   * Test if any of the supplied options are set 
   * @param string $option option list 
   * @return bool
   */
  public function hasAny( string ...$option ) : bool
  {
    foreach( $option as $o )
    {
      if ( isset( $this->options[$o] ))
        return true;
    }
    
    return false;
  }  
}
