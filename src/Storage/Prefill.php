<?php

namespace Bolt\Storage;

use Bolt\Application;
use Guzzle\Service\Client;

/**
 * Handles Fetching Prefill Content from an API service
 */
class Prefill
{
    
   /**
    * Constructor function
    *
    *  @param Guzzle\Service\Client $client 
    *  @return void
    **/
    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    
    
   /**
    * Fetches the content from the service
    *
    * @param string $request Parameters to add to the base uri - eg /medium/decorate/ol
    * @return array
    */
    public function get($request, $base = 'http://loripsum.net/api/')
    {
        $uri = $base. ltrim($request, '/'); 
        return $this->client->get($uri, array('timeout' => 10))->send()->getBody(true);
    }

}
