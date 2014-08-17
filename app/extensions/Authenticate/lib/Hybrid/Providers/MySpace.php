<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_MySpace provider adapter based on OAuth1 protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_MySpace.html
 */
class Hybrid_Providers_MySpace extends Hybrid_Provider_Model_OAuth1
{
	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->api_endpoint_url  = "http://api.myspace.com/v1/";
		$this->api->authorize_url     = "http://api.myspace.com/authorize";
		$this->api->request_token_url = "http://api.myspace.com/request_token";
		$this->api->access_token_url  = "http://api.myspace.com/access_token";
	}

	/**
	* get the connected uid from myspace api
	*/
	public function getCurrentUserId()
	{
		$response = $this->api->get( 'http://api.myspace.com/v1/user.json' );

		if ( ! isset( $response->userId ) ){
			throw new Exception( "User id request failed! {$this->providerId} returned an invalid response." );
		}

		return $response->userId;
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		$userId = $this->getCurrentUserId();

		$data = $this->api->get( 'http://api.myspace.com/v1/users/' . $userId . '/profile.json' );

		if ( ! is_object( $data ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier  = $userId;
		$this->user->profile->displayName = $data->basicprofile->name;
		$this->user->profile->description = $data->aboutme;
		$this->user->profile->gender      = $data->basicprofile->gender;
		$this->user->profile->photoURL    = $data->basicprofile->image;
		$this->user->profile->profileURL  = $data->basicprofile->webUri;
		$this->user->profile->age         = $data->age;
		$this->user->profile->country     = $data->country;
		$this->user->profile->region      = $data->region;
		$this->user->profile->city        = $data->city;
		$this->user->profile->zip         = $data->postalcode;

		return $this->user->profile;
	}

	/**
	* load the user contacts
	*/
	function getUserContacts()
	{
		$userId = $this->getCurrentUserId();

		$response = $this->api->get( "http://api.myspace.com/v1/users/" . $userId . "/friends.json" );

		if ( ! is_object( $response ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$contacts = ARRAY();

		foreach( $response->Friends as $item ){ 
			$uc = new Hybrid_User_Contact();

			$uc->identifier   = $item->userId;
			$uc->displayName  = $item->name;
			$uc->profileURL   = $item->webUri;
			$uc->photoURL     = $item->image;
			$uc->description  = $item->status; 

			$contacts[] = $uc;
		}

		return $contacts;
 	}

	/**
	* update user status
	*/
	function setUserStatus( $status )
	{
	// crappy myspace... gonna see this asaic
		$userId = $this->getCurrentUserId();
		
		$parameters = array( 'status' => $status );

		$response = $this->api->api( "http://api.myspace.com/v1/users/" . $userId . "/status", 'PUT', $parameters ); 

		// check the last HTTP status code returned
		if ( $this->api->http_code != 200 )
		{
			throw new Exception( "Update user status failed! {$this->providerId} returned an error. " . $this->errorMessageByStatus( $this->api->http_code ) );
		}
 	}

	/**
	* load the user latest activity  
	*    - timeline : all the stream
	*    - me       : the user activity only  
	*/
	function getUserActivity( $stream )
	{
		$userId = $this->getCurrentUserId();

		if( $stream == "me" ){
			$response = $this->api->get( "http://api.myspace.com/v1/users/" . $userId . "/status.json" ); 
		}                                                          
		else{                                                      
			$response = $this->api->get( "http://api.myspace.com/v1/users/" . $userId . "/friends/status.json" );
		}

		if ( ! is_object( $response ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$activities = ARRAY();

		if( $stream == "me" ){
			// todo
		}                                                          
		else{                                                      
			foreach( $response->FriendsStatus as $item ){
				$ua = new Hybrid_User_Activity();

				$ua->id                 = $item->statusId;
				$ua->date               = NULL; // to find out!!
				$ua->text               = $item->status;

				$ua->user->identifier   = $item->user->userId;
				$ua->user->displayName  = $item->user->name;
				$ua->user->profileURL   = $item->user->uri;
				$ua->user->photoURL     = $item->user->image;

				$activities[] = $ua;
			}
		} 

		return $activities;
 	}
}
