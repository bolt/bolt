<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
* Hybrid_Providers_Twitter provider adapter based on OAuth1 protocol
*/
class Hybrid_Providers_Twitter extends Hybrid_Provider_Model_OAuth1
{
	/**
	* IDp wrappers initializer 
	*/
	function initialize()
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_base_url      = "https://api.twitter.com/1.1/";
		$this->api->authorize_url     = "https://api.twitter.com/oauth/authenticate";
		$this->api->request_token_url = "https://api.twitter.com/oauth/request_token";
		$this->api->access_token_url  = "https://api.twitter.com/oauth/access_token";

		if ( isset( $this->config['api_version'] ) && $this->config['api_version'] ){
			$this->api->api_base_url  = "https://api.twitter.com/{$this->config['api_version']}/";
		}
 
		if ( isset( $this->config['authorize'] ) && $this->config['authorize'] ){
			$this->api->authorize_url = "https://api.twitter.com/oauth/authorize";
		}

		$this->api->curl_auth_header  = false;
	}

 	/**
 	 * begin login step
 	 */
 	function loginBegin()
 	{
 		$tokens = $this->api->requestToken( $this->endpoint );
 	
 		// request tokens as recived from provider
 		$this->request_tokens_raw = $tokens;
 	
 		// check the last HTTP status code returned
 		if ( $this->api->http_code != 200 ){
 			throw new Exception( "Authentification failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 5 );
 		}
 	
 		if ( ! isset( $tokens["oauth_token"] ) ){
 			throw new Exception( "Authentification failed! {$this->providerId} returned an invalid oauth token.", 5 );
 		}
 	
 		$this->token( "request_token"       , $tokens["oauth_token"] );
 		$this->token( "request_token_secret", $tokens["oauth_token_secret"] );
 	
		// redirect the user to the provider authentication url with force_login
 		if ( isset( $this->config['force_login'] ) && $this->config['force_login'] ){
 			Hybrid_Auth::redirect( $this->api->authorizeUrl( $tokens, array( 'force_login' => true ) ) );
 		}

		// else, redirect the user to the provider authentication url
 		Hybrid_Auth::redirect( $this->api->authorizeUrl( $tokens ) );
 	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$response = $this->api->get( 'account/verify_credentials.json' );

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ), 6 );
		}

		if ( ! is_object( $response ) || ! isset( $response->id ) ){
			throw new Exception( "User profile request failed! {$this->providerId} api returned an invalid response.", 6 );
		}

		# store the user profile.  
		$this->user->profile->identifier  = (property_exists($response,'id'))?$response->id:"";
		$this->user->profile->displayName = (property_exists($response,'screen_name'))?$response->screen_name:"";
		$this->user->profile->description = (property_exists($response,'description'))?$response->description:"";
		$this->user->profile->firstName   = (property_exists($response,'name'))?$response->name:""; 
		$this->user->profile->photoURL    = (property_exists($response,'profile_image_url'))?$response->profile_image_url:"";
		$this->user->profile->profileURL  = (property_exists($response,'screen_name'))?("http://twitter.com/".$response->screen_name):"";
		$this->user->profile->webSiteURL  = (property_exists($response,'url'))?$response->url:""; 
		$this->user->profile->region      = (property_exists($response,'location'))?$response->location:"";

		return $this->user->profile;
 	}

	/**
	* load the user contacts
	*/
	function getUserContacts()
	{
		$parameters = array( 'cursor' => '-1' ); 
		$response  = $this->api->get( 'friends/ids.json', $parameters ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if( ! $response || ! count( $response->ids ) ){
			return ARRAY();
		}

		// 75 id per time should be okey
		$contactsids = array_chunk ( $response->ids, 75 );

		$contacts    = ARRAY(); 

		foreach( $contactsids as $chunk ){ 
			$parameters = array( 'user_id' => implode( ",", $chunk ) ); 
			$response   = $this->api->get( 'users/lookup.json', $parameters ); 

			// check the last HTTP status code returned
			if ( $this->api->http_code != 200 ){
				throw new Exception( "User contacts request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
			}

			if( $response && count( $response ) ){
				foreach( $response as $item ){ 
					$uc = new Hybrid_User_Contact();

					$uc->identifier   = (property_exists($item,'id'))?$item->id:"";
					$uc->displayName  = (property_exists($item,'name'))?$item->name:"";
					$uc->profileURL   = (property_exists($item,'screen_name'))?("http://twitter.com/".$item->screen_name):"";
					$uc->photoURL     = (property_exists($item,'profile_image_url'))?$item->profile_image_url:"";
					$uc->description  = (property_exists($item,'description'))?$item->description:""; 

					$contacts[] = $uc;
				} 
			} 
		}

		return $contacts;
 	}

	/**
	* update user status
	*/ 
	function setUserStatus( $status )
	{
		$parameters = array( 'status' => $status ); 
		$response  = $this->api->post( 'statuses/update.json', $parameters ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "Update user status failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}
 	}

	/**
	* load the user latest activity  
	*    - timeline : all the stream
	*    - me       : the user activity only  
	*
	* by default return the timeline
	*/ 
	function getUserActivity( $stream )
	{
		if( $stream == "me" ){
			$response  = $this->api->get( 'statuses/user_timeline.json' ); 
		}                                                          
		else{
			$response  = $this->api->get( 'statuses/home_timeline.json' ); 
		}

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 ){
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}

		if( ! $response ){
			return ARRAY();
		}

		$activities = ARRAY();

		foreach( $response as $item ){
			$ua = new Hybrid_User_Activity();

			$ua->id                 = (property_exists($item,'id'))?$item->id:"";
			$ua->date               = (property_exists($item,'created_at'))?strtotime($item->created_at):"";
			$ua->text               = (property_exists($item,'text'))?$item->text:"";

			$ua->user->identifier   = (property_exists($item->user,'id'))?$item->user->id:"";
			$ua->user->displayName  = (property_exists($item->user,'name'))?$item->user->name:""; 
			$ua->user->profileURL   = (property_exists($item->user,'screen_name'))?("http://twitter.com/".$item->user->screen_name):"";
			$ua->user->photoURL     = (property_exists($item->user,'profile_image_url'))?$item->user->profile_image_url:"";
			
			$activities[] = $ua;
		}

		return $activities;
 	}
}
