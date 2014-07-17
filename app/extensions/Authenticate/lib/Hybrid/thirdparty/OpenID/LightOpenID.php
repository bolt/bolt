<?php
// https://github.com/iignatov/LightOpenID

/**
 * This class provides a simple interface for OpenID 1.1/2.0 authentication.
 * 
 * It requires PHP >= 5.1.2 with cURL or HTTP/HTTPS stream wrappers enabled.
 *
 * @version v1.1.2 2013-01-15
 * @link http://gitorious.org/lightopenid Official Repo
 * @link http://github.com/iignatov/LightOpenID GitHub Clone
 * @author Mewp
 * @copyright Copyright (c) 2010, Mewp
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
class LightOpenID
{
    public $returnUrl
         , $required = array()
         , $optional = array()
         , $verify_peer = null
         , $capath = null
         , $cainfo = null
         , $data
         , $oauth = array();
    private $identity, $claimed_id;
    protected $server, $version, $trustRoot, $aliases, $identifier_select = false
            , $ax = false, $sreg = false, $setup_url = null, $headers = array(), $proxy = null
            , $xrds_override_pattern = null, $xrds_override_replacement = null;
    static protected $ax_to_sreg = array(
        'namePerson/friendly'     => 'nickname',
        'contact/email'           => 'email',
        'namePerson'              => 'fullname',
        'birthDate'               => 'dob',
        'person/gender'           => 'gender',
        'contact/postalCode/home' => 'postcode',
        'contact/country/home'    => 'country',
        'pref/language'           => 'language',
        'pref/timezone'           => 'timezone',
        );

    function __construct($host, $proxy = null)
    {
        $this->trustRoot = (strpos($host, '://') ? $host : 'http://' . $host);
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')
        ) {
            $this->trustRoot = (strpos($host, '://') ? $host : 'https://' . $host);
        }

        if(strlen($this->trustRoot >= 8) && ($host_end = strpos($this->trustRoot, '/', 8)) !== false) {
            $this->trustRoot = substr($this->trustRoot, 0, $host_end);
        }
        
        $this->set_proxy($proxy);

        $uri = rtrim(preg_replace('#((?<=\?)|&)openid\.[^&]+#', '', $_SERVER['REQUEST_URI']), '?');
        $this->returnUrl = $this->trustRoot . $uri;

        $this->data = ($_SERVER['REQUEST_METHOD'] === 'POST') ? $_POST : $_GET;

        if(!function_exists('curl_init') && !in_array('https', stream_get_wrappers())) {
            throw new ErrorException('You must have either https wrappers or curl enabled.');
        }
    }

    function __set($name, $value)
    {
        switch ($name) {
        case 'identity':
            if (strlen($value = trim((String) $value))) {
                if (preg_match('#^xri:/*#i', $value, $m)) {
                    $value = substr($value, strlen($m[0]));
                } elseif (!preg_match('/^(?:[=@+\$!\(]|https?:)/i', $value)) {
                    $value = "http://$value";
                }
                if (preg_match('#^https?://[^/]+$#i', $value, $m)) {
                    $value .= '/';
                }
            }
            $this->$name = $this->claimed_id = $value;
            break;
        case 'trustRoot':
        case 'realm':
            $this->trustRoot = trim($value);
            break;
        case 'xrdsOverride':
            if (is_array($value)) {
                list($pattern, $replacement) = $value;
                $this->xrds_override_pattern = $pattern;
                $this->xrds_override_replacement = $replacement;
            } else {
                trigger_error('Invalid value specified for "xrdsOverride".', E_USER_ERROR);
            }
            break;
        }
    }

    function __get($name)
    {
        switch ($name) {
        case 'identity':
            # We return claimed_id instead of identity,
            # because the developer should see the claimed identifier,
            # i.e. what he set as identity, not the op-local identifier (which is what we verify)
            return $this->claimed_id;
        case 'trustRoot':
        case 'realm':
            return $this->trustRoot;
        case 'mode':
            return empty($this->data['openid_mode']) ? null : $this->data['openid_mode'];
        }
    }
    
    function set_proxy($proxy)
    {
        if (!empty($proxy)) {
            // When the proxy is a string - try to parse it.
            if (!is_array($proxy)) {
                $proxy = parse_url($proxy);
            }
            
            // Check if $proxy is valid after the parsing.
            if ($proxy && !empty($proxy['host'])) {
                // Make sure that a valid port number is specified.
                if (array_key_exists('port', $proxy)) {
                    if (!is_int($proxy['port'])) {
                        $proxy['port'] = is_numeric($proxy['port']) ? intval($proxy['port']) : 0;
                    }
                    
                    if ($proxy['port'] <= 0) {
                        throw new ErrorException('The specified proxy port number is invalid.');
                    }
                }
                
                $this->proxy = $proxy;
            }
        }
    }

    /**
     * Checks if the server specified in the url exists.
     *
     * @param $url url to check
     * @return true, if the server exists; false otherwise
     */
    function hostExists($url)
    {
        if (strpos($url, '/') === false) {
            $server = $url;
        } else {
            $server = @parse_url($url, PHP_URL_HOST);
        }

        if (!$server) {
            return false;
        }

        return !!gethostbynamel($server);
    }

    protected function request_curl($url, $method='GET', $params=array(), $update_claimed_id)
    {
        $params = http_build_query($params, '', '&');
        $curl = curl_init($url . ($method == 'GET' && $params ? '?' . $params : ''));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Accept: application/xrds+xml, */*'));
        
        if (!empty($this->proxy)) {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxy['host']);
            
            if (!empty($this->proxy['port'])) {
                curl_setopt($curl, CURLOPT_PROXYPORT, $this->proxy['port']);
            }
            
            if (!empty($this->proxy['user'])) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->proxy['user'] . ':' . $this->proxy['pass']);            
            }
        }

        if($this->verify_peer !== null) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verify_peer);
            if($this->capath) {
                curl_setopt($curl, CURLOPT_CAPATH, $this->capath);
            }

            if($this->cainfo) {
                curl_setopt($curl, CURLOPT_CAINFO, $this->cainfo);
            }
        }

        if ($method == 'POST') {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        } elseif ($method == 'HEAD') {
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_NOBODY, true);
        } else {
            curl_setopt($curl, CURLOPT_HEADER, true);
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        $response = curl_exec($curl);
        if( $response === FALSE ) {
            Hybrid_Logger::error( "LightOpenID::request_curl(). curl_exec error: ", curl_error($curl) );
        }

        if($method == 'HEAD' && curl_getinfo($curl, CURLINFO_HTTP_CODE) == 405) {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
            $response = curl_exec($curl);
            $response = substr($response, 0, strpos($response, "\r\n\r\n"));
        }

        if($method == 'HEAD' || $method == 'GET') {
            $header_response = $response;

            # If it's a GET request, we want to only parse the header part.
            if($method == 'GET') {
                $header_response = substr($response, 0, strpos($response, "\r\n\r\n"));
            }

            $headers = array();
            foreach(explode("\n", $header_response) as $header) {
                $pos = strpos($header,':');
                if ($pos !== false) {
                    $name = strtolower(trim(substr($header, 0, $pos)));
                    $headers[$name] = trim(substr($header, $pos+1));
                }
            }

            if($update_claimed_id) {
                # Updating claimed_id in case of redirections.
                $effective_url = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
                if($effective_url != $url) {
                    $this->identity = $this->claimed_id = $effective_url;
                }
            }

            if($method == 'HEAD') {
                return $headers;
            } else {
                $this->headers = $headers;
            }
        }

        if (curl_errno($curl)) {
            throw new ErrorException(curl_error($curl), curl_errno($curl));
        }

        return $response;
    }

    protected function parse_header_array($array, $update_claimed_id)
    {
        $headers = array();
        foreach($array as $header) {
            $pos = strpos($header,':');
            if ($pos !== false) {
                $name = strtolower(trim(substr($header, 0, $pos)));
                $headers[$name] = trim(substr($header, $pos+1));

                # Following possible redirections. The point is just to have
                # claimed_id change with them, because the redirections
                # are followed automatically.
                # We ignore redirections with relative paths.
                # If any known provider uses them, file a bug report.
                if($name == 'location' && $update_claimed_id) {
                    if(strpos($headers[$name], 'http') === 0) {
                        $this->identity = $this->claimed_id = $headers[$name];
                    } elseif($headers[$name][0] == '/') {
                        $parsed_url = parse_url($this->claimed_id);
                        $this->identity =
                        $this->claimed_id = $parsed_url['scheme'] . '://'
                                          . $parsed_url['host']
                                          . $headers[$name];
                    }
                }
            }
        }
        return $headers;
    }

    protected function request_streams($url, $method='GET', $params=array(), $update_claimed_id)
    {
        if(!$this->hostExists($url)) {
            throw new ErrorException("Could not connect to $url.", 404);
        }

        $params = http_build_query($params, '', '&');
        switch($method) {
        case 'GET':
            $opts = array(
                'http' => array(
                    'method' => 'GET',
                    'header' => 'Accept: application/xrds+xml, */*',
                    'ignore_errors' => true,
                ), 'ssl' => array(
                    'CN_match' => parse_url($url, PHP_URL_HOST),
                ),
            );
            $url = $url . ($params ? '?' . $params : '');
            if (!empty($this->proxy)) {
                $opts['http']['proxy'] = $this->proxy_url();
            }
            break;
        case 'POST':
            $opts = array(
                'http' => array(
                    'method' => 'POST',
                    'header'  => 'Content-type: application/x-www-form-urlencoded',
                    'content' => $params,
                    'ignore_errors' => true,
                ), 'ssl' => array(
                    'CN_match' => parse_url($url, PHP_URL_HOST),
                ),
            );
            if (!empty($this->proxy)) {
                $opts['http']['proxy'] = $this->proxy_url();
            }
            break;
        case 'HEAD':
            // We want to send a HEAD request, but since get_headers() doesn't 
            // accept $context parameter, we have to change the defaults.
            $default = stream_context_get_options(stream_context_get_default());
            
            // PHP does not reset all options. Instead, it just sets the options
            // available in the passed array, therefore set the defaults manually.
            $default += array(
                'http' => array(),
                'ssl' => array()
            );
            $default['http'] += array(
                'method' => 'GET',
                'header' => '',
                'ignore_errors' => false
            );
            $default['ssl'] += array(
                'CN_match' => ''
            );
            
            $opts = array(
                'http' => array(
                    'method' => 'HEAD',
                    'header' => 'Accept: application/xrds+xml, */*',
                    'ignore_errors' => true,
                ),
                'ssl' => array(
                    'CN_match' => parse_url($url, PHP_URL_HOST)
                )
            );
            
            // Enable validation of the SSL certificates.
            if ($this->verify_peer) {
                $default['ssl'] += array(
                    'verify_peer' => false,
                    'capath' => '',
                    'cafile' => ''
                );
                $opts['ssl'] += array(
                    'verify_peer' => true,
                    'capath' => $this->capath,
                    'cafile' => $this->cainfo
                );
            }
            
            // Change the stream context options.
            stream_context_get_default($opts);
            
            $headers = get_headers($url . ($params ? '?' . $params : ''));
            
            // Restore the stream context options.
            stream_context_get_default($default);
            
            if (!empty($headers)) {
                if (intval(substr($headers[0], strlen('HTTP/1.1 '))) == 405) {
                    // The server doesn't support HEAD - emulate it with a GET.
                    $args = func_get_args();
                    $args[1] = 'GET';
                    call_user_func_array(array($this, 'request_streams'), $args);
                    $headers = $this->headers;
                } else {
                    $headers = $this->parse_header_array($headers, $update_claimed_id);
                }
            } else {
                $headers = array();
            }
            
            return $headers;
        }

        if ($this->verify_peer) {
            $opts['ssl'] += array(
                'verify_peer' => true,
                'capath'      => $this->capath,
                'cafile'      => $this->cainfo
            );
        }

        $context = stream_context_create ($opts);
        $data = file_get_contents($url, false, $context);
        # This is a hack for providers who don't support HEAD requests.
        # It just creates the headers array for the last request in $this->headers.
        if(isset($http_response_header)) {
            $this->headers = $this->parse_header_array($http_response_header, $update_claimed_id);
        }

        return $data;
    }

    protected function request($url, $method='GET', $params=array(), $update_claimed_id=false)
    {
        if (function_exists('curl_init')
            && (!in_array('https', stream_get_wrappers()) || !ini_get('safe_mode') && !ini_get('open_basedir'))
        ) {
            return $this->request_curl($url, $method, $params, $update_claimed_id);
        }
        return $this->request_streams($url, $method, $params, $update_claimed_id);
    }
    
    protected function proxy_url()
    {
        $result = '';
        
        if (!empty($this->proxy)) {
            $result = $this->proxy['host'];
            
            if (!empty($this->proxy['port'])) {
                $result = $result . ':' . $this->proxy['port'];
            }
            
            if (!empty($this->proxy['user'])) {
                $result = $this->proxy['user'] . ':' . $this->proxy['pass'] . '@' . $result;
            }
            
            $result = 'http://' . $result;
        }
        
        return $result;
    }

    protected function build_url($url, $parts)
    {
        if (isset($url['query'], $parts['query'])) {
            $parts['query'] = $url['query'] . '&' . $parts['query'];
        }

        $url = $parts + $url;
        $url = $url['scheme'] . '://'
             . (empty($url['username'])?''
                 :(empty($url['password'])? "{$url['username']}@"
                 :"{$url['username']}:{$url['password']}@"))
             . $url['host']
             . (empty($url['port'])?'':":{$url['port']}")
             . (empty($url['path'])?'':$url['path'])
             . (empty($url['query'])?'':"?{$url['query']}")
             . (empty($url['fragment'])?'':"#{$url['fragment']}");
        return $url;
    }

    /**
     * Helper function used to scan for <meta>/<link> tags and extract information
     * from them
     */
    protected function htmlTag($content, $tag, $attrName, $attrValue, $valueName)
    {
        preg_match_all("#<{$tag}[^>]*$attrName=['\"].*?$attrValue.*?['\"][^>]*$valueName=['\"](.+?)['\"][^>]*/?>#i", $content, $matches1);
        preg_match_all("#<{$tag}[^>]*$valueName=['\"](.+?)['\"][^>]*$attrName=['\"].*?$attrValue.*?['\"][^>]*/?>#i", $content, $matches2);

        $result = array_merge($matches1[1], $matches2[1]);
        return empty($result)?false:$result[0];
    }

    /**
     * Performs Yadis and HTML discovery. Normally not used.
     * @param $url Identity URL.
     * @return String OP Endpoint (i.e. OpenID provider address).
     * @throws ErrorException
     */
    function discover($url)
    {
        if (!$url) throw new ErrorException('No identity supplied.');
        # Use xri.net proxy to resolve i-name identities
        if (!preg_match('#^https?:#', $url)) {
            $url = "https://xri.net/$url";
        }

        # We save the original url in case of Yadis discovery failure.
        # It can happen when we'll be lead to an XRDS document
        # which does not have any OpenID2 services.
        $originalUrl = $url;

        # A flag to disable yadis discovery in case of failure in headers.
        $yadis = true;
        
        # Allows optional regex replacement of the URL, e.g. to use Google Apps
        # as an OpenID provider without setting up XRDS on the domain hosting.
        if (!is_null($this->xrds_override_pattern) && !is_null($this->xrds_override_replacement)) {
            $url = preg_replace($this->xrds_override_pattern, $this->xrds_override_replacement, $url);
        }

        # We'll jump a maximum of 5 times, to avoid endless redirections.
        for ($i = 0; $i < 5; $i ++) {
            if ($yadis) {
                $headers = $this->request($url, 'HEAD', array(), true);

                $next = false;
                if (isset($headers['x-xrds-location'])) {
                    $url = $this->build_url(parse_url($url), parse_url(trim($headers['x-xrds-location'])));
                    $next = true;
                }

                if (isset($headers['content-type'])
                    && (strpos($headers['content-type'], 'application/xrds+xml') !== false
                        || strpos($headers['content-type'], 'text/xml') !== false)
                ) {
                    # Apparently, some providers return XRDS documents as text/html.
                    # While it is against the spec, allowing this here shouldn't break
                    # compatibility with anything.
                    # ---
                    # Found an XRDS document, now let's find the server, and optionally delegate.
                    $content = $this->request($url, 'GET');

                    preg_match_all('#<Service.*?>(.*?)</Service>#s', $content, $m);
                    foreach($m[1] as $content) {
                        $content = ' ' . $content; # The space is added, so that strpos doesn't return 0.

                        # OpenID 2
                        $ns = preg_quote('http://specs.openid.net/auth/2.0/', '#');
                        if(preg_match('#<Type>\s*'.$ns.'(server|signon)\s*</Type>#s', $content, $type)) {
                            if ($type[1] == 'server') $this->identifier_select = true;

                            preg_match('#<URI.*?>(.*)</URI>#', $content, $server);
                            preg_match('#<(Local|Canonical)ID>(.*)</\1ID>#', $content, $delegate);
                            if (empty($server)) {
                                return false;
                            }
                            # Does the server advertise support for either AX or SREG?
                            $this->ax   = (bool) strpos($content, '<Type>http://openid.net/srv/ax/1.0</Type>');
                            $this->sreg = strpos($content, '<Type>http://openid.net/sreg/1.0</Type>')
                                       || strpos($content, '<Type>http://openid.net/extensions/sreg/1.1</Type>');

                            $server = $server[1];
                            if (isset($delegate[2])) $this->identity = trim($delegate[2]);
                            $this->version = 2;

                            $this->server = $server;
                            return $server;
                        }

                        # OpenID 1.1
                        $ns = preg_quote('http://openid.net/signon/1.1', '#');
                        if (preg_match('#<Type>\s*'.$ns.'\s*</Type>#s', $content)) {

                            preg_match('#<URI.*?>(.*)</URI>#', $content, $server);
                            preg_match('#<.*?Delegate>(.*)</.*?Delegate>#', $content, $delegate);
                            if (empty($server)) {
                                return false;
                            }
                            # AX can be used only with OpenID 2.0, so checking only SREG
                            $this->sreg = strpos($content, '<Type>http://openid.net/sreg/1.0</Type>')
                                       || strpos($content, '<Type>http://openid.net/extensions/sreg/1.1</Type>');

                            $server = $server[1];
                            if (isset($delegate[1])) $this->identity = $delegate[1];
                            $this->version = 1;

                            $this->server = $server;
                            return $server;
                        }
                    }

                    $next = true;
                    $yadis = false;
                    $url = $originalUrl;
                    $content = null;
                    break;
                }
                if ($next) continue;

                # There are no relevant information in headers, so we search the body.
                $content = $this->request($url, 'GET', array(), true);

                if (isset($this->headers['x-xrds-location'])) {
                    $url = $this->build_url(parse_url($url), parse_url(trim($this->headers['x-xrds-location'])));
                    continue;
                }

                $location = $this->htmlTag($content, 'meta', 'http-equiv', 'X-XRDS-Location', 'content');
                if ($location) {
                    $url = $this->build_url(parse_url($url), parse_url($location));
                    continue;
                }
            }

            if (!$content) $content = $this->request($url, 'GET');

            # At this point, the YADIS Discovery has failed, so we'll switch
            # to openid2 HTML discovery, then fallback to openid 1.1 discovery.
            $server   = $this->htmlTag($content, 'link', 'rel', 'openid2.provider', 'href');
            $delegate = $this->htmlTag($content, 'link', 'rel', 'openid2.local_id', 'href');
            $this->version = 2;

            if (!$server) {
                # The same with openid 1.1
                $server   = $this->htmlTag($content, 'link', 'rel', 'openid.server', 'href');
                $delegate = $this->htmlTag($content, 'link', 'rel', 'openid.delegate', 'href');
                $this->version = 1;
            }

            if ($server) {
                # We found an OpenID2 OP Endpoint
                if ($delegate) {
                    # We have also found an OP-Local ID.
                    $this->identity = $delegate;
                }
                $this->server = $server;
                return $server;
            }

            throw new ErrorException("No OpenID Server found at $url", 404);
        }
        throw new ErrorException('Endless redirection!', 500);
    }

    protected function sregParams()
    {
        $params = array();
        # We always use SREG 1.1, even if the server is advertising only support for 1.0.
        # That's because it's fully backwards compatible with 1.0, and some providers
        # advertise 1.0 even if they accept only 1.1. One such provider is myopenid.com
        $params['openid.ns.sreg'] = 'http://openid.net/extensions/sreg/1.1';
        if ($this->required) {
            $params['openid.sreg.required'] = array();
            foreach ($this->required as $required) {
                if (!isset(self::$ax_to_sreg[$required])) continue;
                $params['openid.sreg.required'][] = self::$ax_to_sreg[$required];
            }
            $params['openid.sreg.required'] = implode(',', $params['openid.sreg.required']);
        }

        if ($this->optional) {
            $params['openid.sreg.optional'] = array();
            foreach ($this->optional as $optional) {
                if (!isset(self::$ax_to_sreg[$optional])) continue;
                $params['openid.sreg.optional'][] = self::$ax_to_sreg[$optional];
            }
            $params['openid.sreg.optional'] = implode(',', $params['openid.sreg.optional']);
        }
        return $params;
    }

    protected function axParams()
    {
        $params = array();
        if ($this->required || $this->optional) {
            $params['openid.ns.ax'] = 'http://openid.net/srv/ax/1.0';
            $params['openid.ax.mode'] = 'fetch_request';
            $this->aliases  = array();
            $counts   = array();
            $required = array();
            $optional = array();
            foreach (array('required','optional') as $type) {
                foreach ($this->$type as $alias => $field) {
                    if (is_int($alias)) $alias = strtr($field, '/', '_');
                    $this->aliases[$alias] = 'http://axschema.org/' . $field;
                    if (empty($counts[$alias])) $counts[$alias] = 0;
                    $counts[$alias] += 1;
                    ${$type}[] = $alias;
                }
            }
            foreach ($this->aliases as $alias => $ns) {
                $params['openid.ax.type.' . $alias] = $ns;
            }
            foreach ($counts as $alias => $count) {
                if ($count == 1) continue;
                $params['openid.ax.count.' . $alias] = $count;
            }

            # Don't send empty ax.required and ax.if_available.
            # Google and possibly other providers refuse to support ax when one of these is empty.
            if($required) {
                $params['openid.ax.required'] = implode(',', $required);
            }
            if($optional) {
                $params['openid.ax.if_available'] = implode(',', $optional);
            }
        }
        return $params;
    }

    protected function authUrl_v1($immediate)
    {
        $returnUrl = $this->returnUrl;
        # If we have an openid.delegate that is different from our claimed id,
        # we need to somehow preserve the claimed id between requests.
        # The simplest way is to just send it along with the return_to url.
        if($this->identity != $this->claimed_id) {
            $returnUrl .= (strpos($returnUrl, '?') ? '&' : '?') . 'openid.claimed_id=' . $this->claimed_id;
        }

        $params = array(
            'openid.return_to'  => $returnUrl,
            'openid.mode'       => $immediate ? 'checkid_immediate' : 'checkid_setup',
            'openid.identity'   => $this->identity,
            'openid.trust_root' => $this->trustRoot,
            ) + $this->sregParams();

        return $this->build_url(parse_url($this->server)
                               , array('query' => http_build_query($params, '', '&')));
    }

    protected function authUrl_v2($immediate)
    {
        $params = array(
            'openid.ns'          => 'http://specs.openid.net/auth/2.0',
            'openid.mode'        => $immediate ? 'checkid_immediate' : 'checkid_setup',
            'openid.return_to'   => $this->returnUrl,
            'openid.realm'       => $this->trustRoot,
        );
        
        if ($this->ax) {
            $params += $this->axParams();
        }
        
        if ($this->sreg) {
            $params += $this->sregParams();
        }
        
        if (!$this->ax && !$this->sreg) {
            # If OP doesn't advertise either SREG, nor AX, let's send them both
            # in worst case we don't get anything in return.
            $params += $this->axParams() + $this->sregParams();
        }

        if (!empty($this->oauth) && is_array($this->oauth)) {
            $params['openid.ns.oauth'] = 'http://specs.openid.net/extensions/oauth/1.0';
            $params['openid.oauth.consumer'] = str_replace(array('http://', 'https://'), '', $this->trustRoot);
            $params['openid.oauth.scope'] = implode(' ', $this->oauth);
        }

        if ($this->identifier_select) {
            $params['openid.identity'] = $params['openid.claimed_id']
                 = 'http://specs.openid.net/auth/2.0/identifier_select';
        } else {
            $params['openid.identity'] = $this->identity;
            $params['openid.claimed_id'] = $this->claimed_id;
        }

        return $this->build_url(parse_url($this->server)
                               , array('query' => http_build_query($params, '', '&')));
    }

    /**
     * Returns authentication url. Usually, you want to redirect your user to it.
     * @return String The authentication url.
     * @param String $select_identifier Whether to request OP to select identity for an user in OpenID 2. Does not affect OpenID 1.
     * @throws ErrorException
     */
    function authUrl($immediate = false)
    {
        if ($this->setup_url && !$immediate) return $this->setup_url;
        if (!$this->server) $this->discover($this->identity);

        if ($this->version == 2) {
            return $this->authUrl_v2($immediate);
        }
        return $this->authUrl_v1($immediate);
    }

    /**
     * Performs OpenID verification with the OP.
     * @return Bool Whether the verification was successful.
     * @throws ErrorException
     */
    function validate()
    {
        # If the request was using immediate mode, a failure may be reported
        # by presenting user_setup_url (for 1.1) or reporting
        # mode 'setup_needed' (for 2.0). Also catching all modes other than
        # id_res, in order to avoid throwing errors.
        if(isset($this->data['openid_user_setup_url'])) {
            $this->setup_url = $this->data['openid_user_setup_url'];
            return false;
        }
        if($this->mode != 'id_res') {
            return false;
        }

        $this->claimed_id = isset($this->data['openid_claimed_id'])?$this->data['openid_claimed_id']:$this->data['openid_identity'];
        $params = array(
            'openid.assoc_handle' => $this->data['openid_assoc_handle'],
            'openid.signed'       => $this->data['openid_signed'],
            'openid.sig'          => $this->data['openid_sig'],
            );

        if (isset($this->data['openid_ns'])) {
            # We're dealing with an OpenID 2.0 server, so let's set an ns
            # Even though we should know location of the endpoint,
            # we still need to verify it by discovery, so $server is not set here
            $params['openid.ns'] = 'http://specs.openid.net/auth/2.0';
        } elseif (isset($this->data['openid_claimed_id'])
            && $this->data['openid_claimed_id'] != $this->data['openid_identity']
        ) {
            # If it's an OpenID 1 provider, and we've got claimed_id,
            # we have to append it to the returnUrl, like authUrl_v1 does.
            $this->returnUrl .= (strpos($this->returnUrl, '?') ? '&' : '?')
                             .  'openid.claimed_id=' . $this->claimed_id;
        }

        if ($this->data['openid_return_to'] != $this->returnUrl) {
            # The return_to url must match the url of current request.
            # I'm assuming that no one will set the returnUrl to something that doesn't make sense.
            return false;
        }

        $server = $this->discover($this->claimed_id);

        foreach (explode(',', $this->data['openid_signed']) as $item) {
            # Checking whether magic_quotes_gpc is turned on, because
            # the function may fail if it is. For example, when fetching
            # AX namePerson, it might contain an apostrophe, which will be escaped.
            # In such case, validation would fail, since we'd send different data than OP
            # wants to verify. stripslashes() should solve that problem, but we can't
            # use it when magic_quotes is off.
            $value = $this->data['openid_' . str_replace('.','_',$item)];
            $params['openid.' . $item] = function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() ? stripslashes($value) : $value;

        }

        $params['openid.mode'] = 'check_authentication';

        $response = $this->request($server, 'POST', $params);

        return preg_match('/is_valid\s*:\s*true/i', $response);
    }

    protected function getAxAttributes()
    {
        $result = array();
        
        if ($alias = $this->getNamespaceAlias('http://openid.net/srv/ax/1.0', 'ax')) {
            $prefix = 'openid_' . $alias;
            $length = strlen('http://axschema.org/');
            
            foreach (explode(',', $this->data['openid_signed']) as $key) {
                $keyMatch = $alias . '.type.';
                
                if (strncmp($key, $keyMatch, strlen($keyMatch)) !== 0) {
                    continue;
                }
                
                $key = substr($key, strlen($keyMatch));
                $idv = $prefix . '_value_' . $key;
                $idc = $prefix . '_count_' . $key;
                $key = substr($this->getItem($prefix . '_type_' . $key), $length);
                
                if (!empty($key)) {
                    if (($count = intval($this->getItem($idc))) > 0) {
                        $value = array();
                        
                        for ($i = 1; $i <= $count; $i++) {
                            $value[] = $this->getItem($idv . '_' . $i);
                        }
                        
                        $value = ($count == 1) ? reset($value) : $value;
                    } else {
                        $value = $this->getItem($idv);
                    }
                    
                    if (!is_null($value)) {
                        $result[$key] = $value;
                    }
                }
            }
        } else {
            // No alias for the AX schema has been found,
            // so there is no AX data in the OP's response.
        }
        
        return $result;
    }

    protected function getSregAttributes()
    {
        $attributes = array();
        $sreg_to_ax = array_flip(self::$ax_to_sreg);
        foreach (explode(',', $this->data['openid_signed']) as $key) {
            $keyMatch = 'sreg.';
            if (strncmp($key, $keyMatch, strlen($keyMatch)) !== 0) {
                continue;
            }
            $key = substr($key, strlen($keyMatch));
            if (!isset($sreg_to_ax[$key])) {
                # The field name isn't part of the SREG spec, so we ignore it.
                continue;
            }
            $attributes[$sreg_to_ax[$key]] = $this->data['openid_sreg_' . $key];
        }
        return $attributes;
    }

    /**
     * Gets AX/SREG attributes provided by OP. should be used only after successful validation.
     * Note that it does not guarantee that any of the required/optional parameters will be present,
     * or that there will be no other attributes besides those specified.
     * In other words. OP may provide whatever information it wants to.
     *     * SREG names will be mapped to AX names.
     *     * @return Array Array of attributes with keys being the AX schema names, e.g. 'contact/email'
     * @see http://www.axschema.org/types/
     */
    function getAttributes()
    {
        if (isset($this->data['openid_ns'])
            && $this->data['openid_ns'] == 'http://specs.openid.net/auth/2.0'
        ) { # OpenID 2.0
            # We search for both AX and SREG attributes, with AX taking precedence.
            return $this->getAxAttributes() + $this->getSregAttributes();
        }
        return $this->getSregAttributes();
    }

    /**
     * Gets an OAuth request token if the OpenID+OAuth hybrid protocol has been used.
     *
     * In order to use the OpenID+OAuth hybrid protocol, you need to add at least one
     * scope to the $openid->oauth array before you get the call to getAuthUrl(), e.g.:
     * $openid->oauth[] = 'https://www.googleapis.com/auth/plus.me';
     * 
     * Furthermore the registered consumer name must fit the OpenID realm. 
     * To register an OpenID consumer at Google use: https://www.google.com/accounts/ManageDomains
     * 
     * @return string|bool OAuth request token on success, FALSE if no token was provided.
     */
    function getOAuthRequestToken()
    {
        $alias = $this->getNamespaceAlias('http://specs.openid.net/extensions/oauth/1.0');
        
        return !empty($alias) ? $this->data['openid_' . $alias . '_request_token'] : false;
    }
    
    /**
     * Gets the alias for the specified namespace, if it's present.
     *
     * @param string $namespace The namespace for which an alias is needed.
     * @param string $hint Common alias of this namespace, used for optimization.
     * @return string|null The namespace alias if found, otherwise - NULL.
     */
    private function getNamespaceAlias($namespace, $hint = null)
    {
        $result = null;
        
        if (empty($hint) || $this->getItem('openid_ns_' . $hint) != $namespace) {
            // The common alias is either undefined or points to
            // some other extension - search for another alias..
            $prefix = 'openid_ns_';
            $length = strlen($prefix);
            
            foreach ($this->data as $key => $val) {
                if (strncmp($key, $prefix, $length) === 0 && $val === $namespace) {
                    $result = trim(substr($key, $length));
                    break;
                }
            }
        } else {
            $result = $hint;
        }
        
        return $result;
    }
    
    /**
     * Gets an item from the $data array by the specified id.
     *
     * @param string $id The id of the desired item.
     * @return string|null The item if found, otherwise - NULL.
     */
    private function getItem($id)
    {
        return isset($this->data[$id]) ? $this->data[$id] : null; 
    }
}
