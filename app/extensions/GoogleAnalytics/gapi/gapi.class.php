<?php
/**
 * GAPI - Google Analytics PHP Interface
 * 
 * http://code.google.com/p/gapi-google-analytics-php-interface/
 * 
 * @copyright Stig Manning 2009
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * @author Stig Manning <stig@sdm.co.nz>
 * @version 1.3
 * 
 */

class gapi
{
  const http_interface = 'auto'; //'auto': autodetect, 'curl' or 'fopen'
  
  const client_login_url = 'https://www.google.com/accounts/ClientLogin';
  const account_data_url = 'https://www.google.com/analytics/feeds/accounts/default';
  const report_data_url = 'https://www.google.com/analytics/feeds/data';
  const interface_name = 'GAPI-1.3';
  const dev_mode = false;
  
  private $auth_token = null;
  private $account_entries = array();
  private $account_root_parameters = array();
  private $report_aggregate_metrics = array();
  private $report_root_parameters = array();
  private $results = array();
  
  /**
   * Constructor function for all new gapi instances
   * 
   * Set up authenticate with Google and get auth_token
   *
   * @param String $email
   * @param String $password
   * @param String $token
   * @return gapi
   */
  public function __construct($email, $password, $token=null)
  {
    if($token !== null)
    {
      $this->auth_token = $token;
    }
    else 
    {
      $this->authenticateUser($email,$password);
    }
  }
  
  /**
   * Return the auth token, used for storing the auth token in the user session
   *
   * @return String
   */
  public function getAuthToken()
  {
    return $this->auth_token;
  }
  
  /**
   * Request account data from Google Analytics
   *
   * @param Int $start_index OPTIONAL: Start index of results
   * @param Int $max_results OPTIONAL: Max results returned
   */
  public function requestAccountData($start_index=1, $max_results=20)
  {
    $response = $this->httpRequest(gapi::account_data_url, array('start-index'=>$start_index,'max-results'=>$max_results), null, $this->generateAuthHeader());
    
    if(substr($response['code'],0,1) == '2')
    {
      return $this->accountObjectMapper($response['body']);
    }
    else 
    {
      throw new Exception('GAPI: Failed to request account data. Error: "' . strip_tags($response['body']) . '"');
    }
  }
  
  /**
   * Request report data from Google Analytics
   *
   * $report_id is the Google report ID for the selected account
   * 
   * $parameters should be in key => value format
   * 
   * @param String $report_id
   * @param Array $dimensions Google Analytics dimensions e.g. array('browser')
   * @param Array $metrics Google Analytics metrics e.g. array('pageviews')
   * @param Array $sort_metric OPTIONAL: Dimension or dimensions to sort by e.g.('-visits')
   * @param String $filter OPTIONAL: Filter logic for filtering results
   * @param String $start_date OPTIONAL: Start of reporting period
   * @param String $end_date OPTIONAL: End of reporting period
   * @param Int $start_index OPTIONAL: Start index of results
   * @param Int $max_results OPTIONAL: Max results returned
   */
  public function requestReportData($report_id, $dimensions, $metrics, $sort_metric=null, $filter=null, $start_date=null, $end_date=null, $start_index=1, $max_results=30)
  {
    $parameters = array('ids'=>'ga:' . $report_id);
    
    if(is_array($dimensions))
    {
      $dimensions_string = '';
      foreach($dimensions as $dimesion)
      {
        $dimensions_string .= ',ga:' . $dimesion;
      }
      $parameters['dimensions'] = substr($dimensions_string,1);
    }
    else 
    {
      $parameters['dimensions'] = 'ga:'.$dimensions;
    }

    if(is_array($metrics))
    {
      $metrics_string = '';
      foreach($metrics as $metric)
      {
        $metrics_string .= ',ga:' . $metric;
      }
      $parameters['metrics'] = substr($metrics_string,1);
    }
    else 
    {
      $parameters['metrics'] = 'ga:'.$metrics;
    }
    
    if($sort_metric==null&&isset($parameters['metrics']))
    {
      $parameters['sort'] = $parameters['metrics'];
    }
    elseif(is_array($sort_metric))
    {
      $sort_metric_string = '';
      
      foreach($sort_metric as $sort_metric_value)
      {
        //Reverse sort - Thanks Nick Sullivan
        if (substr($sort_metric_value, 0, 1) == "-")
        {
          $sort_metric_string .= ',-ga:' . substr($sort_metric_value, 1); // Descending
        }
        else
        {
          $sort_metric_string .= ',ga:' . $sort_metric_value; // Ascending
        }
      }
      
      $parameters['sort'] = substr($sort_metric_string, 1);
    }
    else 
    {
      if (substr($sort_metric, 0, 1) == "-")
      {
        $parameters['sort'] = '-ga:' . substr($sort_metric, 1);
      }
      else 
      {
        $parameters['sort'] = 'ga:' . $sort_metric;
      }
    }
    
    if($filter!=null)
    {
      $filter = $this->processFilter($filter);
      if($filter!==false)
      {
        $parameters['filters'] = $filter;
      }
    }
    
    if($start_date==null)
    {
      $start_date=date('Y-m-d',strtotime('1 month ago'));
    }
    
    $parameters['start-date'] = $start_date;
    
    if($end_date==null)
    {
      $end_date=date('Y-m-d');
    }
    
    $parameters['end-date'] = $end_date;
    
    
    $parameters['start-index'] = $start_index;
    $parameters['max-results'] = $max_results;
    
    $parameters['prettyprint'] = gapi::dev_mode ? 'true' : 'false';
    
    $response = $this->httpRequest(gapi::report_data_url, $parameters, null, $this->generateAuthHeader());
    
    //HTTP 2xx
    if(substr($response['code'],0,1) == '2')
    {
      return $this->reportObjectMapper($response['body']);
    }
    else 
    {
      throw new Exception('GAPI: Failed to request report data. Error: "' . strip_tags($response['body']) . '"');
    }
  }

  /**
   * Process filter string, clean parameters and convert to Google Analytics
   * compatible format
   * 
   * @param String $filter
   * @return String Compatible filter string
   */
  protected function processFilter($filter)
  {
    $valid_operators = '(!~|=~|==|!=|>|<|>=|<=|=@|!@)';
    
    $filter = preg_replace('/\s\s+/',' ',trim($filter)); //Clean duplicate whitespace
    $filter = str_replace(array(',',';'),array('\,','\;'),$filter); //Escape Google Analytics reserved characters
    $filter = preg_replace('/(&&\s*|\|\|\s*|^)([a-z]+)(\s*' . $valid_operators . ')/i','$1ga:$2$3',$filter); //Prefix ga: to metrics and dimensions
    $filter = preg_replace('/[\'\"]/i','',$filter); //Clear invalid quote characters
    $filter = preg_replace(array('/\s*&&\s*/','/\s*\|\|\s*/','/\s*' . $valid_operators . '\s*/'),array(';',',','$1'),$filter); //Clean up operators
    
    if(strlen($filter)>0)
    {
      return urlencode($filter);
    }
    else 
    {
      return false;
    }
  }
  
  /**
   * Report Account Mapper to convert the XML to array of useful PHP objects
   *
   * @param String $xml_string
   * @return Array of gapiAccountEntry objects
   */
  protected function accountObjectMapper($xml_string)
  {
    $xml = simplexml_load_string($xml_string);
    
    $this->results = null;
    
    $results = array();
    $account_root_parameters = array();
    
    //Load root parameters
    
    $account_root_parameters['updated'] = strval($xml->updated);
    $account_root_parameters['generator'] = strval($xml->generator);
    $account_root_parameters['generatorVersion'] = strval($xml->generator->attributes());
    
    $open_search_results = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');
    
    foreach($open_search_results as $key => $open_search_result)
    {
      $report_root_parameters[$key] = intval($open_search_result);
    }
    
    $account_root_parameters['startDate'] = strval($google_results->startDate);
    $account_root_parameters['endDate'] = strval($google_results->endDate);
    
    //Load result entries
    
    foreach($xml->entry as $entry)
    {
      $properties = array();
      foreach($entry->children('http://schemas.google.com/analytics/2009')->property as $property)
      {
        $properties[str_replace('ga:','',$property->attributes()->name)] = strval($property->attributes()->value);
      }
      
      $properties['title'] = strval($entry->title);
      $properties['updated'] = strval($entry->updated);
      
      $results[] = new gapiAccountEntry($properties);
    }
    
    $this->account_root_parameters = $account_root_parameters;
    $this->results = $results;
    
    return $results;
  }
  
  
  /**
   * Report Object Mapper to convert the XML to array of useful PHP objects
   *
   * @param String $xml_string
   * @return Array of gapiReportEntry objects
   */
  protected function reportObjectMapper($xml_string)
  {
    $xml = simplexml_load_string($xml_string);
    
    $this->results = null;
    $results = array();
    
    $report_root_parameters = array();
    $report_aggregate_metrics = array();
    
    //Load root parameters
    
    $report_root_parameters['updated'] = strval($xml->updated);
    $report_root_parameters['generator'] = strval($xml->generator);
    $report_root_parameters['generatorVersion'] = strval($xml->generator->attributes());
    
    $open_search_results = $xml->children('http://a9.com/-/spec/opensearchrss/1.0/');
    
    foreach($open_search_results as $key => $open_search_result)
    {
      $report_root_parameters[$key] = intval($open_search_result);
    }
    
    $google_results = $xml->children('http://schemas.google.com/analytics/2009');

    foreach($google_results->dataSource->property as $property_attributes)
    {
      $report_root_parameters[str_replace('ga:','',$property_attributes->attributes()->name)] = strval($property_attributes->attributes()->value);
    }
    
    $report_root_parameters['startDate'] = strval($google_results->startDate);
    $report_root_parameters['endDate'] = strval($google_results->endDate);
    
    //Load result aggregate metrics
    
    foreach($google_results->aggregates->metric as $aggregate_metric)
    {
      $metric_value = strval($aggregate_metric->attributes()->value);
      
      //Check for float, or value with scientific notation
      if(preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/',$metric_value))
      {
        $report_aggregate_metrics[str_replace('ga:','',$aggregate_metric->attributes()->name)] = floatval($metric_value);
      }
      else
      {
        $report_aggregate_metrics[str_replace('ga:','',$aggregate_metric->attributes()->name)] = intval($metric_value);
      }
    }
    
    //Load result entries
    
    foreach($xml->entry as $entry)
    {
      $metrics = array();
      foreach($entry->children('http://schemas.google.com/analytics/2009')->metric as $metric)
      {
        $metric_value = strval($metric->attributes()->value);
        
        //Check for float, or value with scientific notation
        if(preg_match('/^(\d+\.\d+)|(\d+E\d+)|(\d+.\d+E\d+)$/',$metric_value))
        {
          $metrics[str_replace('ga:','',$metric->attributes()->name)] = floatval($metric_value);
        }
        else
        {
          $metrics[str_replace('ga:','',$metric->attributes()->name)] = intval($metric_value);
        }
      }
      
      $dimensions = array();
      foreach($entry->children('http://schemas.google.com/analytics/2009')->dimension as $dimension)
      {
        $dimensions[str_replace('ga:','',$dimension->attributes()->name)] = strval($dimension->attributes()->value);
      }
      
      $results[] = new gapiReportEntry($metrics,$dimensions);
    }
    
    $this->report_root_parameters = $report_root_parameters;
    $this->report_aggregate_metrics = $report_aggregate_metrics;
    $this->results = $results;
    
    return $results;
  }
  
  /**
   * Authenticate Google Account with Google
   *
   * @param String $email
   * @param String $password
   */
  protected function authenticateUser($email, $password)
  {
    $post_variables = array(
      'accountType' => 'GOOGLE',
      'Email' => $email,
      'Passwd' => $password,
      'source' => gapi::interface_name,
      'service' => 'analytics'
    );
    
    $response = $this->httpRequest(gapi::client_login_url,null,$post_variables);
    
    //Convert newline delimited variables into url format then import to array
    parse_str(str_replace(array("\n","\r\n"),'&',$response['body']),$auth_token);
    
    if(substr($response['code'],0,1) != '2' || !is_array($auth_token) || empty($auth_token['Auth']))
    {
      throw new Exception('GAPI: Failed to authenticate user. Error: "' . strip_tags($response['body']) . '"');
    }
    
    $this->auth_token = $auth_token['Auth'];
  }
  
  /**
   * Generate authentication token header for all requests
   *
   * @return Array
   */
  protected function generateAuthHeader()
  {
    return array('Authorization: GoogleLogin auth=' . $this->auth_token);
  }
  
  /**
   * Perform http request
   * 
   *
   * @param Array $get_variables
   * @param Array $post_variables
   * @param Array $headers
   */
  protected function httpRequest($url, $get_variables=null, $post_variables=null, $headers=null)
  {
    $interface = gapi::http_interface;
    
    if(gapi::http_interface =='auto')
    {
      if(function_exists('curl_exec'))
      {
        $interface = 'curl';
      }
      else 
      {
        $interface = 'fopen';
      }
    }
    
    if($interface == 'curl')
    {
      return $this->curlRequest($url, $get_variables, $post_variables, $headers);
    }
    elseif($interface == 'fopen') 
    {
      return $this->fopenRequest($url, $get_variables, $post_variables, $headers);
    }
    else 
    {
      throw new Exception('Invalid http interface defined. No such interface "' . gapi::http_interface . '"');
    }
  }
  
  /**
   * HTTP request using PHP CURL functions
   * Requires curl library installed and configured for PHP
   * 
   * @param Array $get_variables
   * @param Array $post_variables
   * @param Array $headers
   */
  private function curlRequest($url, $get_variables=null, $post_variables=null, $headers=null)
  {
    $ch = curl_init();
    
    if(is_array($get_variables))
    {
      $get_variables = '?' . str_replace('&amp;','&',urldecode(http_build_query($get_variables)));
    }
    else 
    {
      $get_variables = null;
    }
    
    curl_setopt($ch, CURLOPT_URL, $url . $get_variables);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //CURL doesn't like google's cert
    
    if(is_array($post_variables))
    {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_variables);
    }
    
    if(is_array($headers))
    {
      curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
    }
    
    $response = curl_exec($ch);
    $code = curl_getinfo($ch,CURLINFO_HTTP_CODE);
    
    curl_close($ch);
    
    return array('body'=>$response,'code'=>$code);
  }
  
  /**
   * HTTP request using native PHP fopen function
   * Requires PHP openSSL
   *
   * @param Array $get_variables
   * @param Array $post_variables
   * @param Array $headers
   */
  private function fopenRequest($url, $get_variables=null, $post_variables=null, $headers=null)
  {
    $http_options = array('method'=>'GET','timeout'=>3);
    
    if(is_array($headers))
    {
      $headers = implode("\r\n",$headers) . "\r\n";
    }
    else 
    {
      $headers = '';
    }
    
    if(is_array($get_variables))
    {
      $get_variables = '?' . str_replace('&amp;','&',urldecode(http_build_query($get_variables)));
    }
    else 
    {
      $get_variables = null;
    }
    
    if(is_array($post_variables))
    {
      $post_variables = str_replace('&amp;','&',urldecode(http_build_query($post_variables)));
      $http_options['method'] = 'POST';
      $headers = "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($post_variables) . "\r\n" . $headers;
      $http_options['header'] = $headers;
      $http_options['content'] = $post_variables;
    }
    else 
    {
      $post_variables = '';
      $http_options['header'] = $headers;
    }
    
    $context = stream_context_create(array('http'=>$http_options));
    $response = @file_get_contents($url . $get_variables, null, $context);  
    
    return array('body'=>$response!==false?$response:'Request failed, fopen provides no further information','code'=>$response!==false?'200':'400');
  }
  
  /**
   * Case insensitive array_key_exists function, also returns
   * matching key.
   *
   * @param String $key
   * @param Array $search
   * @return String Matching array key
   */
  public static function array_key_exists_nc($key, $search)
  {
    if (array_key_exists($key, $search))
    {
      return $key;
    }
    if (!(is_string($key) && is_array($search)))
    {
      return false;
    }
    $key = strtolower($key);
    foreach ($search as $k => $v)
    {
      if (strtolower($k) == $key)
      {
        return $k;
      }
    }
    return false;
  }
  
  /**
   * Get Results
   *
   * @return Array
   */
  public function getResults()
  {
    if(is_array($this->results))
    {
      return $this->results;
    }
    else 
    {
      return;
    }
  }
  
  
  /**
   * Get an array of the metrics and the matchning
   * aggregate values for the current result
   *
   * @return Array
   */
  public function getMetrics()
  {
    return $this->report_aggregate_metrics;
  }
  
  /**
   * Call method to find a matching root parameter or 
   * aggregate metric to return
   *
   * @param $name String name of function called
   * @return String
   * @throws Exception if not a valid parameter or aggregate 
   * metric, or not a 'get' function
   */
  public function __call($name,$parameters)
  {
    if(!preg_match('/^get/',$name))
    {
      throw new Exception('No such function "' . $name . '"');
    }
    
    $name = preg_replace('/^get/','',$name);
    
    $parameter_key = gapi::array_key_exists_nc($name,$this->report_root_parameters);
    
    if($parameter_key)
    {
      return $this->report_root_parameters[$parameter_key];
    }
    
    $aggregate_metric_key = gapi::array_key_exists_nc($name,$this->report_aggregate_metrics);
    
    if($aggregate_metric_key)
    {
      return $this->report_aggregate_metrics[$aggregate_metric_key];
    }

    throw new Exception('No valid root parameter or aggregate metric called "' . $name . '"');
  }
}

/**
 * Class gapiAccountEntry
 * 
 * Storage for individual gapi account entries
 *
 */
class gapiAccountEntry
{
  private $properties = array();
  
  public function __construct($properties)
  {
    $this->properties = $properties;
  }
  
  /**
   * toString function to return the name of the account
   *
   * @return String
   */
  public function __toString()
  {
    if(isset($this->properties['title']))
    {
      return $this->properties['title'];
    }
    else 
    {
      return;
    }
  }
  
  /**
   * Get an associative array of the properties
   * and the matching values for the current result
   *
   * @return Array
   */
  public function getProperties()
  {
    return $this->properties;
  }
  
  /**
   * Call method to find a matching parameter to return
   *
   * @param $name String name of function called
   * @return String
   * @throws Exception if not a valid parameter, or not a 'get' function
   */
  public function __call($name,$parameters)
  {
    if(!preg_match('/^get/',$name))
    {
      throw new Exception('No such function "' . $name . '"');
    }
    
    $name = preg_replace('/^get/','',$name);
    
    $property_key = gapi::array_key_exists_nc($name,$this->properties);
    
    if($property_key)
    {
      return $this->properties[$property_key];
    }
    
    throw new Exception('No valid property called "' . $name . '"');
  }
}

/**
 * Class gapiReportEntry
 * 
 * Storage for individual gapi report entries
 *
 */
class gapiReportEntry
{
  private $metrics = array();
  private $dimensions = array();
  
  public function __construct($metrics,$dimesions)
  {
    $this->metrics = $metrics;
    $this->dimensions = $dimesions;
  }
  
  /**
   * toString function to return the name of the result
   * this is a concatented string of the dimesions chosen
   * 
   * For example:
   * 'Firefox 3.0.10' from browser and browserVersion
   *
   * @return String
   */
  public function __toString()
  {
    if(is_array($this->dimensions))
    {
      return implode(' ',$this->dimensions);
    }
    else 
    {
      return '';
    }
  }
  
  /**
   * Get an associative array of the dimesions
   * and the matching values for the current result
   *
   * @return Array
   */
  public function getDimesions()
  {
    return $this->dimensions;
  }
  
  /**
   * Get an array of the metrics and the matchning
   * values for the current result
   *
   * @return Array
   */
  public function getMetrics()
  {
    return $this->metrics;
  }
  
  /**
   * Call method to find a matching metric or dimension to return
   *
   * @param $name String name of function called
   * @return String
   * @throws Exception if not a valid metric or dimensions, or not a 'get' function
   */
  public function __call($name,$parameters)
  {
    if(!preg_match('/^get/',$name))
    {
      throw new Exception('No such function "' . $name . '"');
    }
    
    $name = preg_replace('/^get/','',$name);
    
    $metric_key = gapi::array_key_exists_nc($name,$this->metrics);
    
    if($metric_key)
    {
      return $this->metrics[$metric_key];
    }
    
    $dimension_key = gapi::array_key_exists_nc($name,$this->dimensions);
    
    if($dimension_key)
    {
      return $this->dimensions[$dimension_key];
    }

    throw new Exception('No valid metric or dimesion called "' . $name . '"');
  }
}