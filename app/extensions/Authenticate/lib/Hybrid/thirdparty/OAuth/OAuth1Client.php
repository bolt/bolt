<?php
/*!
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2012, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

// A service client for the OAuth 1/1.0a flow.
// v0.1
class OAuth1Client{
	public $api_base_url          = "";
	public $authorize_url         = "";
	public $authenticate_url      = "";
	public $request_token_url     = "";
	public $access_token_url      = "";
	
	public $request_token_method  = "GET";
	public $access_token_method   = "GET";

	public $redirect_uri          = "";

	public $decode_json           = true;
	public $curl_time_out         = 30;
	public $curl_connect_time_out = 30;
	public $curl_ssl_verifypeer   = false;
	public $curl_auth_header      = true;
	public $curl_useragent        = "OAuth/1 Simple PHP Client v0.1; HybridAuth http://hybridauth.sourceforge.net/";
	public $curl_proxy            = null;

	//--

	public $http_code             = "";
	public $http_info             = "";

	/** 
	* OAuth client constructor
	*/ 
	function __construct( $consumer_key, $consumer_secret, $oauth_token = null, $oauth_token_secret = null )
	{
		$this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
		$this->consumer    = new OAuthConsumer( $consumer_key, $consumer_secret );
		$this->token       = null;

		if ( $oauth_token && $oauth_token_secret ){
			$this->token = new OAuthConsumer( $oauth_token, $oauth_token_secret );
		} 
	}

	/** 
	* Build authorize url
	* 
	* @return string 
	*/ 
	function authorizeUrl( $token, $extras =array() )
	{
		if ( is_array( $token ) ){
			$token = $token['oauth_token'];
		}

		$parameters = array( "oauth_token" => $token );

		if( count($extras) )
			foreach( $extras as $k=>$v )
				$parameters[$k] = $v;

		return $this->authorize_url . "?" . http_build_query( $parameters );
    }

	/**
	* Get a request_token from provider
	* 
	* @return array a key/value array containing oauth_token and oauth_token_secret 
	*/
	function requestToken( $callback = null )
	{
		$parameters = array(); 

		if ( $callback ) {
			$this->redirect_uri = $parameters['oauth_callback'] = $callback; 
		}

		$request     = $this->signedRequest( $this->request_token_url, $this->request_token_method, $parameters ); 
		$token       = OAuthUtil::parse_parameters( $request );
		$this->token = new OAuthConsumer( $token['oauth_token'], $token['oauth_token_secret'] ); 

		return $token; 
	} 

	/** 
	* Exchange the request token and secret for an access token and secret, to sign API calls. 
	* 
	* @return array array('oauth_token' => the access token, 'oauth_token_secret' => the access secret) 
	*/
	function accessToken( $oauth_verifier = false, $oauth_token = false )
	{
		$parameters = array();

		// 1.0a
		if ( $oauth_verifier ) {
			$parameters['oauth_verifier'] = $oauth_verifier; 
		}

		$request     = $this->signedRequest( $this->access_token_url, $this->access_token_method, $parameters ); 
		$token       = OAuthUtil::parse_parameters( $request ); 
		$this->token = new OAuthConsumer( $token['oauth_token'], $token['oauth_token_secret'] ); 

		return $token;
	}

	/** 
	* GET wrappwer for provider apis request
	*/
	function get($url, $parameters = array())
	{
		return $this->api($url, 'GET', $parameters); 
	} 

	/** 
	* POST wreapper for provider apis request
	*/
	function post($url, $parameters = array())
	{
		return $this->api($url, 'POST', $parameters); 
	}

	/** 
	* Format and sign an oauth for provider api 
	*/ 
	function api( $url, $method = 'GET', $parameters = array() )
	{
		if ( strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0 ) {
			$url = $this->api_base_url . $url;
		}

		$response = $this->signedRequest( $url, $method, $parameters );

		if( $this->decode_json ){
			$response = json_decode( $response );
		}

		return $response; 
	} 

	/** 
	* Make signed request  
	*/ 
	function signedRequest( $url, $method, $parameters )
	{
		$request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
		$request->sign_request($this->sha1_method, $this->consumer, $this->token);
		switch ($method) {
			case 'GET': return $this->request( $request->to_url(), 'GET' );
			default   : return $this->request( $request->get_normalized_http_url(), $method, $request->to_postdata(), $request->to_header() ) ;
		}
	}
	
	/** 
	* Make http request  
	*/ 
	function request( $url, $method, $postfields = NULL, $auth_header = null )
	{
		Hybrid_Logger::info( "Enter OAuth1Client::request( $method, $url )" );
		Hybrid_Logger::debug( "OAuth1Client::request(). dump post fields: ", serialize( $postfields ) ); 

		$this->http_info = array();
		$ci = curl_init();

		/* Curl settings */
		curl_setopt( $ci, CURLOPT_USERAGENT     , $this->curl_useragent );
		curl_setopt( $ci, CURLOPT_CONNECTTIMEOUT, $this->curl_connect_time_out );
		curl_setopt( $ci, CURLOPT_TIMEOUT       , $this->curl_time_out );
		curl_setopt( $ci, CURLOPT_RETURNTRANSFER, TRUE );
		curl_setopt( $ci, CURLOPT_HTTPHEADER    , array('Expect:') );
		curl_setopt( $ci, CURLOPT_SSL_VERIFYPEER, $this->curl_ssl_verifypeer );
		curl_setopt( $ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader') );
		curl_setopt( $ci, CURLOPT_HEADER        , FALSE );
		
		if($this->curl_proxy){
			curl_setopt( $ci, CURLOPT_PROXY        , $this->curl_proxy);
		}
 
		switch ($method){
			case 'POST':
				curl_setopt( $ci, CURLOPT_POST, TRUE );

				if ( !empty($postfields) ){
					curl_setopt( $ci, CURLOPT_POSTFIELDS, $postfields );
				}

				if ( !empty($auth_header) && $this->curl_auth_header ){
					curl_setopt( $ci, CURLOPT_HTTPHEADER, array( 'Content-Type: application/atom+xml', $auth_header ) );
				}
				break;
			case 'DELETE':
				curl_setopt( $ci, CURLOPT_CUSTOMREQUEST, 'DELETE' );
				if ( !empty($postfields) ){
				  $url = "{$url}?{$postfields}";
				}
		}

		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);

		Hybrid_Logger::debug( "OAuth1Client::request(). dump request info: ", serialize( curl_getinfo($ci) ) );
		Hybrid_Logger::debug( "OAuth1Client::request(). dump request result: ", serialize( $response ) );

		$this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ci));

		curl_close ($ci);

		return $response; 
	}

	/**
	* Get the header info to store.
	*/
	function getHeader($ch, $header) {
		$i = strpos($header, ':');

		if ( !empty($i) ){
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->http_header[$key] = $value;
		}

		return strlen($header);
	} 
} 
