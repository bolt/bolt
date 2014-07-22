<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

require_once realpath( dirname( __FILE__ ) )  . "/StorageInterface.php";

/**
 * HybridAuth storage manager
 */
class Hybrid_Storage implements Hybrid_Storage_Interface
{
	/**
	 * Constructor
	 */
	function __construct()
	{ 
		if ( ! session_id() ){
			if( ! session_start() ){
				throw new Exception( "Hybridauth requires the use of 'session_start()' at the start of your script, which appears to be disabled.", 1 );
			}
		}

		$this->config( "php_session_id", session_id() );
		$this->config( "version", Hybrid_Auth::$version );
	}
	
	/**
	 * Config
	 * @param String $key
	 * @param String $value
	 */
	public function config($key, $value = null) 
	{
		$key = strtolower( $key );  

		if( $value ){
			$_SESSION["HA::CONFIG"][$key] = serialize( $value ); 
		}
		elseif( isset( $_SESSION["HA::CONFIG"][$key] ) ){ 
			return unserialize( $_SESSION["HA::CONFIG"][$key] );  
		}

		return NULL; 
	}
	
	/**
	 * Get a key
	 * @param String $key
	 */
	public function get($key) 
	{
		$key = strtolower( $key );  

		if( isset( $_SESSION["HA::STORE"], $_SESSION["HA::STORE"][$key] ) ){ 
			return unserialize( $_SESSION["HA::STORE"][$key] );  
		}

		return NULL; 
	}
	
	/**
	 * GEt a set of key and value
	 * @param String $key
	 * @param String $value
	 */
	public function set( $key, $value )
	{
		$key = strtolower( $key );
                
                if(is_array($value))
                {
                    $value = implode($value);
                }

		$_SESSION["HA::STORE"][$key] = serialize( (string)$value );
	}
	
	/**
	 * Clear session storage
	 */
	function clear()
	{ 
		$_SESSION["HA::STORE"] = ARRAY(); 
	}
	
	/**
	 * Delete a specific key
	 * @param String $key
	 */
	function delete($key)
	{
		$key = strtolower( $key );  

		if( isset( $_SESSION["HA::STORE"], $_SESSION["HA::STORE"][$key] ) ){
		    $f = $_SESSION['HA::STORE'];
		    unset($f[$key]);
		    $_SESSION["HA::STORE"] = $f;
		} 
	}
	
	/**
	 * Delete a set
	 * @param String $key
	 */
	function deleteMatch($key)
	{
		$key = strtolower( $key ); 

		if( isset( $_SESSION["HA::STORE"] ) && count( $_SESSION["HA::STORE"] ) ) {
		    $f = $_SESSION['HA::STORE'];
		    foreach( $f as $k => $v ){ 
				if( strstr( $k, $key ) ){
					unset( $f[ $k ] ); 
				}
			}
			$_SESSION["HA::STORE"] = $f;
			
		}
	}
	
	/**
	 * Get the storage session data into an array
	 * @return Array
	 */
	function getSessionData()
	{
		if( isset( $_SESSION["HA::STORE"] ) ){ 
			return serialize( $_SESSION["HA::STORE"] ); 
		}

		return NULL; 
	}
	
	/**
	 * Restore the storage back into session from an array
	 * @param Array $sessiondata
	 */
	function restoreSessionData( $sessiondata = NULL )
	{ 
		$_SESSION["HA::STORE"] = unserialize( $sessiondata );
	} 
}
