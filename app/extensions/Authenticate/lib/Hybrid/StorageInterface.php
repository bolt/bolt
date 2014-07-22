<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * HybridAuth storage manager interface
 */
interface Hybrid_Storage_Interface
{
    public function config($key, $value);

    public function get($key);

    public function set( $key, $value );

    function clear();

    function delete($key);

    function deleteMatch($key);

    function getSessionData();

    function restoreSessionData( $sessiondata);
}
