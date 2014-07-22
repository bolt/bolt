<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/
 
/**
 * Debugging and Logging manager
 */
class Hybrid_Logger
{
	/**
	 * Constructor
	 */
	function __construct()
	{
 		// if debug mode is set to true, then check for the writable log file
 		if ( Hybrid_Auth::$config["debug_mode"] ){
                        if ( ! isset(Hybrid_Auth::$config["debug_file"]) ) {
                            throw new Exception( "'debug_mode' is set to 'true' but no log file path 'debug_file' is set.", 1 );
                        }
 			elseif ( ! file_exists( Hybrid_Auth::$config["debug_file"] ) && ! is_writable( Hybrid_Auth::$config["debug_file"]) ){
                                if ( ! touch( Hybrid_Auth::$config["debug_file"] ) ){
                                        throw new Exception( "'debug_mode' is set to 'true', but the file " . Hybrid_Auth::$config['debug_file'] . " in 'debug_file' can not be created.", 1 );
                                }
			}
			elseif ( ! is_writable( Hybrid_Auth::$config["debug_file"] ) ){
				throw new Exception( "'debug_mode' is set to 'true', but the given log file path 'debug_file' is not a writable file.", 1 );
			}
		} 
	}
	
	/**
	 * Debug
	 * @param String $message
	 * @param Object $object
	 */
	public static function debug( $message, $object = NULL )
	{
		if( Hybrid_Auth::$config["debug_mode"] ){
			$datetime = new DateTime();
			$datetime =  $datetime->format(DATE_ATOM);

			file_put_contents( 
				Hybrid_Auth::$config["debug_file"], 
				"DEBUG -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . " -- " . print_r($object, true) . "\n", 
				FILE_APPEND
			);
		}
	}
	
	/**
	 * Info
	 * @param String $message
	 */
	public static function info( $message )
	{ 
		if( in_array(Hybrid_Auth::$config["debug_mode"], array(true, 'info'), true) ){
			$datetime = new DateTime();
			$datetime =  $datetime->format(DATE_ATOM);

			file_put_contents( 
				Hybrid_Auth::$config["debug_file"], 
				"INFO -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . "\n", 
				FILE_APPEND
			);
		}
	}
	
	/**
	 * Error
	 * @param String $message Error message
	 * @param Object $object
	 */
	public static function error($message, $object = NULL)
	{ 
		if( in_array(Hybrid_Auth::$config["debug_mode"], array(true, 'info', 'error'), true) ){
			$datetime = new DateTime();
			$datetime =  $datetime->format(DATE_ATOM);

			file_put_contents( 
				Hybrid_Auth::$config["debug_file"], 
				"ERROR -- " . $_SERVER['REMOTE_ADDR'] . " -- " . $datetime . " -- " . $message . " -- " . print_r($object, true) . "\n", 
				FILE_APPEND
			);
		}
	}
}
