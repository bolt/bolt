<?php
// NiceUrls Extension for Bolt, by WeDesignIt, Patrick van Kouteren

namespace NiceUrls;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Bolt\BaseExtension as BoltExtension;

class Extension extends BoltExtension
{

    /**
     * Info block for NiceUrls Extension.
     */
    function info()
    {

        $data = array(
            'name' => "NiceUrls",
            'description' => "Allows some shortcuts and nicer urls like example.org/about to link through to example.org/page/about",
            'author' => "WeDesignIt, Patrick van Kouteren",
            'link' => "http://www.wedesignit.nl",
            'version' => "0.4",
            'required_bolt_version' => "1.0 RC",
            'highest_bolt_version' => "1.1",
            'type' => "General",
            'first_releasedate' => "2012-11-06",
            'latest_releasedate' => "2013-04-08"
        );

        return $data;

    }

    /**
     * Initialize NiceUrls. Called during bootstrap phase.
     * For subrequests in Silex, see
     * https://github.com/fabpot/Silex/blob/master/doc/cookbook/sub_requests.rst
     */
    function initialize()
    {
        $yamlparser = new \Symfony\Component\Yaml\Parser();
        $config = $yamlparser->parse(file_get_contents(__DIR__ . '/config.yml'));
        foreach ($config as $routingData) {
            if ($this->isValidRoutingData($routingData)) {
                $from = $this->transformWildCard($routingData['from']['slug']);
                $app = $this->app;
                $this->app->match('/' . $from, function (Request $request) use ($app, $from, $routingData) {
                    $app['end'] = 'frontend';
                    $to = \NiceUrls\Extension::transformWildCard($routingData['to']['contenttypeslug'] . '/' . $routingData['to']['slug']);
                    foreach ($request->get('_route_params') as $rparam => $rval) {
                        $to = str_replace('{' . $rparam . '}', $rval, $to);
                    }
                    $uri = $request->getUriForPath('/' . $to);
                    $subRequest = Request::create($uri, 'GET', array(), $request->cookies->all(), array(), $request->server->all());

                    if ($request->getSession()) {
                        $subRequest->setSession($request->getSession());
                    }

                    return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
                })
                    ->assert('contenttypeslug', $this->app['storage']->getContentTypeAssert());
            }
        }
    }

    /**
     * Transforms YML-safe wildcard %% to enclosure in curly braces for symfony
     * routing.
     * @param $string
     * @return mixed
     */
    public static function transformWildCard($string)
    {
        preg_match_all('/%%[A-Za-z0-9]+%%/', $string, $matches);
        $parts = $matches[0];
        if (!empty($parts)) {
            foreach ($parts as $part) {
                $newpart = preg_replace('/%%(.*)%%/', '{$1}', $part);
                $string = str_replace($part, $newpart, $string);
            }
        }
        return $string;
    }

    function isValidRoutingData($routingData)
    {
        if (!array_key_exists('from', $routingData)) {
            return false;
        }
        if (!array_key_exists('to', $routingData)) {
            return false;
        }
        if (!array_key_exists('slug', $routingData['from'])) {
            return false;
        }
        if (!array_key_exists('slug', $routingData['to'])) {
            return false;
        }
        if (!array_key_exists('contenttypeslug', $routingData['to'])) {
            return false;
        }
        return true;
    }

}
