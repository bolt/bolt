<?php
/**
* HybridAuth
* http://hybridauth.sourceforge.net | http://github.com/hybridauth/hybridauth
* (c) 2009-2014, HybridAuth authors | http://hybridauth.sourceforge.net/licenses.html 
*/

// A service client for the OAuth 2 flow.
// v0.1
class OAuth2Client
{
	public $api_base_url     = "";
	public $authorize_url    = "";
	public $token_url        = "";
	public $token_info_url   = "";

	public $client_id        = "" ;
	public $client_secret    = "" ;
	public $redirect_uri     = "" ;
	public $access_token     = "" ;
	public $refresh_token    = "" ;

	public $access_token_expires_in = "" ;
	public $access_token_expires_at = "" ;

	//--

	public $sign_token_name          = "access_token";
	public $decode_json              = true;
	public $curl_time_out            = 30;
	public $curl_connect_time_out    = 30;
	public $curl_ssl_verifypeer      = false;
	public $curl_ssl_verifyhost      = false;
	public $curl_header              = array();
	public $curl_useragent           = "OAuth/2 Simple PHP Client v0.1; HybridAuth http://hybridauth.sourceforge.net/";
	public $curl_authenticate_method = "POST";
        public $curl_proxy               = null;

	//--

	public $http_code             = "";
	public $http_info             = "";

	//--

	public function __construct( $client_id = false, $client_secret = false, $redirect_uri='' )
	{
		$this->client_id     = $client_id;
		$this->client_secret = $client_secret; 
		$this->redirect_uri  = $redirect_uri; 
	}

	public function authorizeUrl( $extras = array() )
	{
		$params = array(
			"client_id"     => $this->client_id,
			"redirect_uri"  => $this->redirect_uri,
			"response_type" => "code"
		);

		if( count($extras) )
			foreach( $extras as $k=>$v )
				$params[$k] = $v;

		return $this->authorize_url . "?" . http_build_query($params, '', '&');
	}

	public function authenticate( $code )
	{
		$params = array(
			"client_id"     => $this->client_id,
			"client_secret" => $this->client_secret,
			"grant_type"    => "authorization_code",
			"redirect_uri"  => $this->redirect_uri,
			"code"          => $code
		);
	
		$response = $this->request( $this->token_url, $params, $this->curl_authenticate_method );
		
		$response = $this->parseRequestResult( $response );

		if( ! $response || ! isset( $response->access_token ) ){
			throw new Exception( "The Authorization Service has return: " . $response->error );
		}

		if( isset( $response->access_token  ) )  $this->access_token           = $response->access_token;
		if( isset( $response->refresh_token ) ) $this->refresh_token           = $response->refresh_token; 
		if( isset( $response->expires_in    ) ) $this->access_token_expires_in = $response->expires_in; 
		
		// calculate when the access token expire
		if( isset($response->expires_in)) {
			$this->access_token_expires_at = time() + $response->expires_in;
		}

		return $response;  
	}

	public function authenticated()
	{
		if ( $this->access_token ){
			if ( $this->token_info_url && $this->refresh_token ){
				// check if this access token has expired, 
				$tokeninfo = $this->tokenInfo( $this->access_token ); 

				// if yes, access_token has expired, then ask for a new one
				if( $tokeninfo && isset( $tokeninfo->error ) ){
					$response = $this->refreshToken( $this->refresh_token ); 

					// if wrong response
					if( ! isset( $response->access_token ) || ! $response->access_token ){
						throw new Exception( "The Authorization Service has return an invalid response while requesting a new access token. given up!" ); 
					}

					// set new access_token
					$this->access_token = $response->access_token; 
				}
			}

			return true;
		}

		return false;
	}

	/** 
	* Format and sign an oauth for provider api 
	*/
	public function api( $url, $method = "GET", $parameters = array() ) 
	{
		if ( strrpos($url, 'http://') !== 0 && strrpos($url, 'https://') !== 0 ) {
			$url = $this->api_base_url . $url;
		}

		$parameters[$this->sign_token_name] = $this->access_token;
		$response = null;

		switch( $method ){
			case 'GET'  : $response = $this->request( $url, $parameters, "GET"  ); break; 
			case 'POST' : $response = $this->request( $url, $parameters, "POST" ); break;
		}

		if( $response && $this->decode_json ){
			$response = json_decode( $response ); 
		}

		return $response; 
	}

	/** 
	* GET wrapper for provider apis request
	*/
	function get( $url, $parameters = array() )
	{
		return $this->api( $url, 'GET', $parameters ); 
	} 

	/** 
	* POST wrapper for provider apis request
	*/
	function post( $url, $parameters = array() )
	{
		return $this->api( $url, 'POST', $parameters ); 
	}

	// -- tokens

	public function tokenInfo($accesstoken)
	{
		$params['access_token'] = $this->access_token;
		$response = $this->request( $this->token_info_url, $params );
		return $this->parseRequestResult( $response );
	}

	public function refreshToken( $parameters = array() )
	{
		$params = array(
			"client_id"     => $this->client_id,
			"client_secret" => $this->client_secret, 
			"grant_type"    => "refresh_token"
		);

		foreach($parameters as $k=>$v ){
			$params[$k] = $v; 
		}

		$response = $this->request( $this->token_url, $params, "POST" );
		return $this->parseRequestResult( $response );
	}

	// -- utilities

	private function request( $url, $params=false, $type="GET" )
	{
		Hybrid_Logger::info( "Enter OAuth2Client::request( $url )" );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request params: ", serialize( $params ) );

		if( $type == "GET" ){
			$url = $url . ( strpos( $url, '?' ) ? '&' : '?' ) . http_build_query($params, '', '&');
		}

		$this->http_info = array();
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL            , $url );
		curl_setopt($ch, CURLOPT_RETURNTRANSFER , 1 );
		curl_setopt($ch, CURLOPT_TIMEOUT        , $this->curl_time_out );
		curl_setopt($ch, CURLOPT_USERAGENT      , $this->curl_useragent );
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT , $this->curl_connect_time_out );
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER , $this->curl_ssl_verifypeer );
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST , $this->curl_ssl_verifyhost );
		curl_setopt($ch, CURLOPT_HTTPHEADER     , $this->curl_header );

		if($this->curl_proxy){
			curl_setopt( $ch, CURLOPT_PROXY        , $this->curl_proxy);
		}

		if( $type == "POST" ){
			curl_setopt($ch, CURLOPT_POST, 1); 
			if($params) curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		}

		$response = curl_exec($ch);
		if( $response === FALSE ) {
				Hybrid_Logger::error( "OAuth2Client::request(). curl_exec error: ", curl_error($ch) );
		}
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request info: ", serialize( curl_getinfo($ch) ) );
		Hybrid_Logger::debug( "OAuth2Client::request(). dump request result: ", serialize( $response ) );

		$this->http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->http_info = array_merge($this->http_info, curl_getinfo($ch));

		curl_close ($ch);

		return $response; 
	}

	private function parseRequestResult( $result )
	{
		if( json_decode( $result ) ) return json_decode( $result );

		parse_str( $result, $output );

		$result = new StdClass();

		foreach( $output as $k => $v )
			$result->$k = $v;

		return $result;
	}
}
