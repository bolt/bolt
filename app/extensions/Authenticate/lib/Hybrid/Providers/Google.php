<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

/**
 * Hybrid_Providers_Google provider adapter based on OAuth2 protocol
 * 
 * http://hybridauth.sourceforge.net/userguide/IDProvider_info_Google.html
 */
class Hybrid_Providers_Google extends Hybrid_Provider_Model_OAuth2
{
    // > more infos on google APIs: http://developer.google.com (official site)
	// or here: http://discovery-check.appspot.com/ (unofficial but up to date)

	// default permissions 
	public $scope = "https://www.googleapis.com/auth/plus.login https://www.googleapis.com/auth/plus.profile.emails.read https://www.google.com/m8/feeds/";

	/**
	* IDp wrappers initializer 
	*/
	function initialize() 
	{
		parent::initialize();

		// Provider api end-points
		$this->api->authorize_url  = "https://accounts.google.com/o/oauth2/auth";
		$this->api->token_url      = "https://accounts.google.com/o/oauth2/token";
		$this->api->token_info_url = "https://www.googleapis.com/oauth2/v2/tokeninfo";
        
		// Override the redirect uri when it's set in the config parameters. This way we prevent
		// redirect uri mismatches when authenticating with Google.
		if( isset( $this->config['redirect_uri'] ) && ! empty( $this->config['redirect_uri'] ) ){
			$this->api->redirect_uri = $this->config['redirect_uri'];
		}
	}

	/**
	* begin login step 
	*/
	function loginBegin()
	{
		$parameters = array("scope" => $this->scope, "access_type" => "offline");
		$optionals  = array("scope", "access_type", "redirect_uri", "approval_prompt", "hd", "state");

		foreach ($optionals as $parameter){
			if( isset( $this->config[$parameter] ) && ! empty( $this->config[$parameter] ) ){
				$parameters[$parameter] = $this->config[$parameter];
			}
			if( isset( $this->config["scope"] ) && ! empty( $this->config["scope"] ) ){
				$this->scope = $this->config["scope"];
			}
		}

		Hybrid_Auth::redirect( $this->api->authorizeUrl( $parameters ) ); 
	}

	/**
	* load the user profile from the IDp api client
	*/
	function getUserProfile()
	{
		// refresh tokens if needed 
		$this->refreshToken();

		// ask google api for user infos
		if (strpos($this->scope, '/auth/plus.profile.emails.read') !== false) {
			$verified = $this->api->api( "https://www.googleapis.com/plus/v1/people/me" );

			if ( ! isset( $verified->id ) || isset( $verified->error ) )
				$verified = new stdClass();
		} else {
			$verified = $this->api->api( "https://www.googleapis.com/plus/v1/people/me/openIdConnect" );

			if ( ! isset( $verified->sub ) || isset( $verified->error ) )
				$verified = new stdClass();
		}

		$response = $this->api->api( "https://www.googleapis.com/plus/v1/people/me" );
		if ( ! isset( $response->id ) || isset( $response->error ) ){
			throw new Exception( "User profile request failed! {$this->providerId} returned an invalid response.", 6 );
		}

		$this->user->profile->identifier    = (property_exists($verified,'id'))?$verified->id:((property_exists($response,'id'))?$response->id:"");
		$this->user->profile->firstName     = (property_exists($response,'name'))?$response->name->givenName:"";
		$this->user->profile->lastName      = (property_exists($response,'name'))?$response->name->familyName:"";
		$this->user->profile->displayName   = (property_exists($response,'displayName'))?$response->displayName:"";
		$this->user->profile->photoURL      = (property_exists($response,'image'))?((property_exists($response->image,'url'))?$response->image->url:''):'';
		$this->user->profile->profileURL    = (property_exists($response,'url'))?$response->url:"";
		$this->user->profile->description   = (property_exists($response,'aboutMe'))?$response->aboutMe:"";
		$this->user->profile->gender        = (property_exists($response,'gender'))?$response->gender:""; 
		$this->user->profile->language      = (property_exists($response,'locale'))?$response->locale:((property_exists($verified,'locale'))?$verified->locale:"");
		$this->user->profile->email         = (property_exists($response,'email'))?$response->email:((property_exists($verified,'email'))?$verified->email:"");
		$this->user->profile->emailVerified = (property_exists($verified,'email'))?$verified->email:"";
		if (property_exists($response, 'emails')) {
			if (count($response->emails) == 1) {
				$this->user->profile->email = $response->emails[0]->value;
			} else {
				foreach ($response->emails as $email) {
					if ($email->type == 'account') {
						$this->user->profile->email = $email->value;
						break;
					}
				}
			}
		}
		$this->user->profile->phone 		= (property_exists($response,'phone'))?$response->phone:"";
		$this->user->profile->country 		= (property_exists($response,'country'))?$response->country:"";
		$this->user->profile->region 		= (property_exists($response,'region'))?$response->region:"";
		$this->user->profile->zip	 		= (property_exists($response,'zip'))?$response->zip:"";
		if( property_exists($response,'placesLived') ){
			$this->user->profile->city 	= ""; 
			$this->user->profile->address	= ""; 
			foreach($response->placesLived as $c){
				if(property_exists($c,'primary')){
					if($c->primary == true){ 
						$this->user->profile->address 	= $c->value;
						$this->user->profile->city 	= $c->value;
					}
				}else{
					if(property_exists($c,'value')){
						$this->user->profile->address 	= $c->value;
						$this->user->profile->city 	= $c->value;
					}	
				}
			}
		}
		
		// google API returns multiple urls, but a "website" only if it is verified 
		// see http://support.google.com/plus/answer/1713826?hl=en
		if( property_exists($response,'urls') ){
			foreach($response->urls as $u){
				if(property_exists($u, 'primary') && $u->primary == true) $this->user->profile->webSiteURL = $u->value;
			}
		} else {
			$this->user->profile->webSiteURL = '';
		}
		// google API returns age ranges or min. age only (with plus.login scope)
		if( property_exists($response,'ageRange') ){
			if( property_exists($response->ageRange,'min') && property_exists($response->ageRange,'max') ){
				$this->user->profile->age 	= $response->ageRange->min.' - '.$response->ageRange->max;
			} else {
				$this->user->profile->age 	= '> '.$response->ageRange->min;
			}
		} else {
			$this->user->profile->age = '';
		}
		// google API returns birthdays only if a user set 'show in my account'
		if( property_exists($response,'birthday') ){ 
			list($birthday_year, $birthday_month, $birthday_day) = explode( '-', $response->birthday );

			$this->user->profile->birthDay   = (int) $birthday_day;
			$this->user->profile->birthMonth = (int) $birthday_month;
			$this->user->profile->birthYear  = (int) $birthday_year;
		} else {
			$this->user->profile->birthDay=0;$this->user->profile->birthMonth=0;$this->user->profile->birthYear=0;
		}
                
		return $this->user->profile;
	}

	/**
	* load the user (Gmail and google plus) contacts 
	*  ..toComplete
	*/
	function getUserContacts()
	{ 
		// refresh tokens if needed 
		$this->refreshToken();  

		$contacts = array(); 
		if( ! isset( $this->config['contacts_param'] ) ){
			$this->config['contacts_param'] = array( "max-results" => 500 );
		}
		
		// Google Gmail and Android contacts
		if (strpos($this->scope, '/m8/feeds/') !== false) {
	
			$response = $this->api->api( "https://www.google.com/m8/feeds/contacts/default/full?" 
						. http_build_query( array_merge( array('alt' => 'json', 'v' => '3.0'), $this->config['contacts_param'] ) ) ); 
	
			if( ! $response ){
				return ARRAY();
			}

			if (isset($response->feed->entry)) {
				foreach( $response->feed->entry as $idx => $entry ){
					$uc = new Hybrid_User_Contact();
					$uc->email 			= isset($entry->{'gd$email'}[0]->address) ? (string) $entry->{'gd$email'}[0]->address : '';
					$uc->displayName 	= isset($entry->title->{'$t'}) ? (string) $entry->title->{'$t'} : '';
					$uc->identifier		= ($uc->email!='')?$uc->email:'';
					$uc->description 	= '';
					if( property_exists($entry,'link') ){
						/**
						 * sign links with access_token
						 */
						if(is_array($entry->link)){
							foreach($entry->link as $l){
								if( property_exists($l,'gd$etag') && $l->type=="image/*"){
									$uc->photoURL = $this->addUrlParam($l->href, array('access_token' => $this->api->access_token));
								} else if($l->type=="self"){
									$uc->profileURL = $this->addUrlParam($l->href, array('access_token' => $this->api->access_token));
								}
							}
						}
					} else {
						$uc->profileURL = '';
					}
					if( property_exists($response,'website') ){
						if(is_array($response->website)){
							foreach($response->website as $w){
								if($w->primary == true) $uc->webSiteURL = $w->value;
							}
						} else {
							$uc->webSiteURL = $response->website->value;
						}
					} else {
						$uc->webSiteURL = '';
					}

					$contacts[] = $uc;
				}
			}
		}
		
		// Google social contacts
		if (strpos($this->scope, '/auth/plus.login') !== false) {
			
			$response = $this->api->api( "https://www.googleapis.com/plus/v1/people/me/people/visible?" 
						. http_build_query( $this->config['contacts_param'] ) ); 
	
			if( ! $response ){
				return ARRAY();
			}
			
			foreach( $response->items as $idx => $item ){
				$uc = new Hybrid_User_Contact();
				$uc->email 			= (property_exists($item,'email'))?$item->email:'';   
				$uc->displayName 	= (property_exists($item,'displayName'))?$item->displayName:'';  
				$uc->identifier 	= (property_exists($item,'id'))?$item->id:'';
				
				$uc->description 	= (property_exists($item,'objectType'))?$item->objectType:'';
				$uc->photoURL 		= (property_exists($item,'image'))?((property_exists($item->image,'url'))?$item->image->url:''):'';
				$uc->profileURL 	= (property_exists($item,'url'))?$item->url:'';
				$uc->webSiteURL 	= '';
				
				$contacts[] = $uc;
			}
			
		}
		
		return $contacts;
 	}

	/**
	 * Add to the $url new parameters
	 * @param string $url
	 * @param array $params
	 * @return string
	 */
	function addUrlParam($url, array $params)
	{
		$query = parse_url($url, PHP_URL_QUERY);

		// Returns the URL string with new parameters
		if( $query ) {
			$url .= '&' . http_build_query( $params );
		} else {
			$url .= '?' . http_build_query( $params );
		}
		return $url;
	}
}
