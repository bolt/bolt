<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | https://github.com/hybridauth/hybridauth
*  (c) 2009-2011 HybridAuth authors | hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_GitHub 
 */
class Hybrid_Providers_GitHub extends Hybrid_Provider_Model_OAuth2
{ 
	// default permissions  
	// (no scope) => public read-only access (includes public user profile info, public repo info, and gists).
	public $scope = "";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url  = "https://api.github.com/";
		$this->api->authorize_url = "https://github.com/login/oauth/authorize";
		$this->api->token_url     = "https://github.com/login/oauth/access_token";
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$data = $this->api->api( "user" ); 

		if ( ! isset( $data->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = @ $data->id; 
		$this->user->profile->displayName = @ $data->name;
		$this->user->profile->description = @ $data->bio;
		$this->user->profile->photoURL    = @ $data->avatar_url;
		$this->user->profile->profileURL  = @ $data->html_url; 
		$this->user->profile->email       = @ $data->email;
		$this->user->profile->webSiteURL  = @ $data->blog;
		$this->user->profile->region      = @ $data->location;

		if( ! $this->user->profile->displayName ){
			$this->user->profile->displayName = @ $data->login;
		}

		return $this->user->profile;
	}
}
