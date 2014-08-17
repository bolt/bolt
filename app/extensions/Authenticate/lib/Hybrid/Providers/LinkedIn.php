<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html
*/

/**
 * Hybrid_Providers_LinkedIn provider adapter based on OAuth1 protocol
 * 
 * Hybrid_Providers_LinkedIn use linkedinPHP library created by fiftyMission Inc.
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_LinkedIn.html
 */
class Hybrid_Providers_LinkedIn extends Hybrid_Provider_Model
{ 
	/**
	* IDp wrappers initializer
	*/
	function initialize()
	{
		if ( ! $this->config["keys"]["key"] || ! $this->config["keys"]["secret"] ){
			throw new Exception( "Your application key and secret are required in order to connect to {$this->providerId}.", 4 );
		}

		require_once Hybrid_Auth::$config["path_libraries"] . "OAuth/OAuth.php";
		require_once Hybrid_Auth::$config["path_libraries"] . "LinkedIn/LinkedIn.php";

		$this->api = new LinkedIn( array( 'appKey' => $this->config["keys"]["key"], 'appSecret' => $this->config["keys"]["secret"], 'callbackUrl' => $this->endpoint ) );

		if( $this->token( "access_token_linkedin" ) ){
			$this->api->setTokenAccess( $this->token( "access_token_linkedin" ) );
		}
	}

	/**
	* begin login step
	*/
	function loginBegin()
	{
		// send a request for a LinkedIn access token
		$response = $this->api->retrieveTokenRequest();

		if( isset( $response['success'] ) && $response['success'] === TRUE ){
			$this->token( "oauth_token",        $response['linkedin']['oauth_token'] );
			$this->token( "oauth_token_secret", $response['linkedin']['oauth_token_secret'] );

			# redirect user to LinkedIn authorisation web page
			Hybrid_Auth::redirect( LINKEDIN::_URL_AUTH . $response['linkedin']['oauth_token'] );
		}
		else{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid Token.", 5 );
		}
	}

	/**
	* finish login step
	*/
	function loginFinish()
	{
		$oauth_token    = $_REQUEST['oauth_token'];
		$oauth_verifier = $_REQUEST['oauth_verifier'];

		if ( ! $oauth_verifier ){
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid Token.", 5 );
		}

		$response = $this->api->retrieveTokenAccess( $oauth_token, $this->token( "oauth_token_secret" ), $oauth_verifier );

		if( isset( $response['success'] ) && $response['success'] === TRUE ){
			$this->deleteToken( "oauth_token"        );
			$this->deleteToken( "oauth_token_secret" );

			$this->token( "access_token_linkedin", $response['linkedin'] );
			$this->token( "access_token"         , $response['linkedin']['oauth_token'] );
			$this->token( "access_token_secret"  , $response['linkedin']['oauth_token_secret'] );

			// set user as logged in
			$this->setUserConnected();
		}
		else{
			throw new Exception( "Authentication failed! {$this->providerId} returned an invalid Token.", 5 );
		}
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		try{
			// http://developer.linkedin.com/docs/DOC-1061
			$response = $this->api->profile('~:(id,first-name,last-name,public-profile-url,picture-url,email-address,date-of-birth,phone-numbers,summary)');
		}
		catch( LinkedInException $e ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an error: $e", 6 );
		}

		if( isset( $response['success'] ) && $response['success'] === TRUE ){
			$data = @ new SimpleXMLElement( $response['linkedin'] );

			if ( ! is_object( $data ) ){
				throw new Exception( "User profile request failed! {$this->providerId} returned an invalid xml data.", 6 );
			}

			$this->user->profile->identifier  = (string) $data->{'id'};
			$this->user->profile->firstName   = (string) $data->{'first-name'};
			$this->user->profile->lastName    = (string) $data->{'last-name'};
			$this->user->profile->displayName = trim( $this->user->profile->firstName . " " . $this->user->profile->lastName );

			$this->user->profile->email         = (string) $data->{'email-address'};
			$this->user->profile->emailVerified = (string) $data->{'email-address'};

			$this->user->profile->photoURL    = (string) $data->{'picture-url'};
			$this->user->profile->profileURL  = (string) $data->{'public-profile-url'};
			$this->user->profile->description = (string) $data->{'summary'};

			if( $data->{'phone-numbers'} && $data->{'phone-numbers'}->{'phone-number'} ){
				$this->user->profile->phone = (string) $data->{'phone-numbers'}->{'phone-number'}->{'phone-number'};
			}
			else{
				$this->user->profile->phone = null;
			}

			if( $data->{'date-of-birth'} ){
				$this->user->profile->birthDay   = (string) $data->{'date-of-birth'}->day;
				$this->user->profile->birthMonth = (string) $data->{'date-of-birth'}->month;
				$this->user->profile->birthYear  = (string) $data->{'date-of-birth'}->year;
			}

			return $this->user->profile;
		}
		else{
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}
	}

	/**
	* load the user contacts
	*/
	function getUserContacts()
	{
		try{
			$response = $this->api->profile('~/connections:(id,first-name,last-name,picture-url,public-profile-url,summary)');
		}
		catch( LinkedInException $e ){
			throw new Exception( "User contacts request failed! {$this->providerId} returned an error: $e" );
		}

		if( ! $response || ! $response['success'] ){
			return ARRAY();
		}

		$connections = new SimpleXMLElement( $response['linkedin'] );
		
		$contacts = ARRAY();

		foreach( $connections->person as $connection ) {
			$uc = new Hybrid_User_Contact();

			$uc->identifier  = (string) $connection->id;
			$uc->displayName = (string) $connection->{'last-name'} . " " . $connection->{'first-name'};
			$uc->profileURL  = (string) $connection->{'public-profile-url'};
			$uc->photoURL    = (string) $connection->{'picture-url'};
			$uc->description = (string) $connection->{'summary'};

			$contacts[] = $uc;
		}

		return $contacts;
	}

	/**
	* update user status
	*/
	function setUserStatus( $status )
	{
		$parameters = array();
		$private    = true; // share with your connections only

		if( is_array( $status ) ){
			if( isset( $status[0] ) && ! empty( $status[0] ) ) $parameters["title"]               = $status[0]; // post title
			if( isset( $status[1] ) && ! empty( $status[1] ) ) $parameters["comment"]             = $status[1]; // post comment
			if( isset( $status[2] ) && ! empty( $status[2] ) ) $parameters["submitted-url"]       = $status[2]; // post url
			if( isset( $status[3] ) && ! empty( $status[3] ) ) $parameters["submitted-image-url"] = $status[3]; // post picture url
			if( isset( $status[4] ) && ! empty( $status[4] ) ) $private                           = $status[4]; // true or false
		}
		else{
			$parameters["comment"] = $status;
		}

		try{
			$response  = $this->api->share( 'new', $parameters, $private );
		}
		catch( LinkedInException $e ){
			throw new Exception( "Update user status update failed!  {$this->providerId} returned an error: $e" );
		}

		if ( ! $response || ! $response['success'] )
		{
			throw new Exception( "Update user status update failed! {$this->providerId} returned an error." );
		}
	}

	/**
	* load the user latest activity
	*    - timeline : all the stream
	*    - me       : the user activity only
	*/
	function getUserActivity( $stream )
	{
		try{
			if( $stream == "me" ){
				$response  = $this->api->updates( '?type=SHAR&scope=self&count=25' );
			}
			else{
				$response  = $this->api->updates( '?type=SHAR&count=25' );
			}
		}
		catch( LinkedInException $e ){
			throw new Exception( "User activity stream request failed! {$this->providerId} returned an error: $e" );
		}

		if( ! $response || ! $response['success'] ){
			return ARRAY();
		}

		$updates = new SimpleXMLElement( $response['linkedin'] );

		$activities = ARRAY(); 

		foreach( $updates->update as $update ) {
			$person = $update->{'update-content'}->person;
			$share  = $update->{'update-content'}->person->{'current-share'};

			$ua = new Hybrid_User_Activity();

			$ua->id                 = (string) $update->id;
			$ua->date               = (string) $update->timestamp;
			$ua->text               = (string) $share->{'comment'};

			$ua->user->identifier   = (string) $person->id;
			$ua->user->displayName  = (string) $person->{'first-name'} . ' ' . $person->{'last-name'};
			$ua->user->profileURL   = (string) $person->{'site-standard-profile-request'}->url;
			$ua->user->photoURL     = NULL;
			
			$activities[] = $ua;
		}

		return $activities;
	}
}
