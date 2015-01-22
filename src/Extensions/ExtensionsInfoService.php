<?php
namespace Bolt\Extensions;

/**
 * Class to provide querying of the Bolt Extensions repo as a 
 * service. 
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/

class ExtensionsInfoService
{
    public $site;
    public $urls;
    public $format = 'json';

    /**
     *
     * @param $site string
     * @param $urls array
     **/
    public function __construct($site, $urls = array())
    {
        $this->site = $site;
        $this->urls = $urls;
    }
    
    
    public function all()
    {
        $url = $this->urls['list'];
        return $this->execute($url);
    }
    
    public function info($package, $bolt)
    {
        $url = $this->urls['info'];
        $params = array('package'=> $package, 'bolt'=> $bolt);
        return $this->execute($url, $params);
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }

    
    
    public function execute($url, $params = array())
    {
        $endpoint = rtrim($this->site, '/') . '/' . ltrim($url, '/') . '?' . http_build_query($params);
        $endpoint = rtrim($endpoint, '?');
        try {
            $result = file_get_contents($endpoint);
        } catch (\Exception $e) {
            $result = false;
        }
        if ($this->format === 'json') {
            return json_decode($result);
        }
        return $result;
    }
}
