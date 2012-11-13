<?php
// NiceUrls Extension for Bolt, by WeDesignIt, Patrick van Kouteren

namespace NiceUrls;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
        'version' => "0.1",
        'required_bolt_version' => "0.7.10",
        'highest_bolt_version' => "0.7.10",
        'type' => "General",
        'first_releasedate' => "2012-11-06",
        'latest_releasedate' => "2012-11-06",
        'dependancies' => "",
        'priority' => 10
    );

    return $data;

}

/**
 * Initialize NiceUrls. Called during bootstrap phase.
 * For subrequests in Silex, see
 * https://github.com/fabpot/Silex/blob/master/doc/cookbook/sub_requests.rst
 */
function init(\Silex\Application $app)
{

    $yamlparser = new \Symfony\Component\Yaml\Parser();
    $config = $yamlparser->parse(file_get_contents(__DIR__ . '/config.yml'));

    foreach ($config as $routingData) {
        if (isValidRoutingData($routingData)) {
            $app->match('/' . $routingData['from']['slug'], function (Request $request) use ($app, $routingData) {
                $app['end'] = 'frontend';
                $uri = $request->getUriForPath("/" . $routingData['to']['contenttypeslug'] . '/' . $routingData['to']['slug']);
                $subRequest = Request::create($uri, 'GET', array(), $request->cookies->all(), array(), $request->server->all());

                if ($request->getSession()) {
                    $subRequest->setSession($request->getSession());
                }

                return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
            });

        }

    }
}

function isValidRoutingData($routingData)
{
    if (!array_key_exists('end', $routingData)) {
        return false;
    }
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