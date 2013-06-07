<?php
// NiceUrls Extension for Bolt, by WeDesignIt, Patrick van Kouteren

namespace NiceUrls;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Bolt\BaseExtension as BoltExtension;

class Extension extends BoltExtension
{
    protected $config;

    /**
     * Info block for NiceUrls Extension.
     */
    public function info()
    {
        $data = array(
            'name' => "NiceUrls",
            'description' => "Allows some shortcuts and nicer urls like example.org/about "
                            ."to link through to example.org/page/about",
            'author' => "WeDesignIt, Patrick van Kouteren, Miguel Angel Gabriel",
            'link' => "http://www.wedesignit.nl",
            'version' => "0.5",
            'required_bolt_version' => "1.1",
            'highest_bolt_version' => "1.1",
            'type' => "General",
            'first_releasedate' => "2012-11-06",
            'latest_releasedate' => "2013-05-29"
        );

        return $data;
    }

    /**
     * Initialize NiceUrls. Called during bootstrap phase.
     * For subrequests in Silex, see
     * https://github.com/fabpot/Silex/blob/master/doc/cookbook/sub_requests.rst
     */
    public function initialize()
    {
        $yamlparser = new \Symfony\Component\Yaml\Parser();
        $this->config = $yamlparser->parse(file_get_contents(__DIR__ . '/config.yml'));

        $this->addTwigFilter('niceurl', 'niceUrlFilter');

        $this->processRouting();
    }

    /**
     * Process routing based on routes definitions from config.yml
     */
    protected function processRouting()
    {
        foreach ($this->config as $routingData) {
            if ($this->isValidRoutingData($routingData)) {
                $from = $this->transformWildCard($routingData['from']['slug']);
                $app = $this->app;
                $me = $this;
                $this->app->match(
                    '/' . $from,
                    function (Request $request) use ($me, $app, $from, $routingData) {
                        $app['end'] = 'frontend';
                        $route = $routingData['to']['contenttypeslug'];
                        $route.= $routingData['to']['slug'] ? '/' . $routingData['to']['slug'] : '';
                        $to = $me->transformWildCard($route);
                        foreach ($request->get('_route_params') as $rparam => $rval) {
                            $to = str_replace('{' . $rparam . '}', $rval, $to);
                        }
                        $uri = $request->getUriForPath('/' . $to);
                        $params = ($request->getMethod() == 'POST')
                                ? $request->request->all()
                                : $request->query->all();

                        $subRequest = Request::create(
                            $uri,
                            $request->getMethod(),
                            $params,
                            $request->cookies->all(),
                            $request->files->all(),
                            $request->server->all()
                        );

                        if ($request->getSession()) {
                            $subRequest->setSession($request->getSession());
                        }

                        return $app->handle($subRequest, HttpKernelInterface::SUB_REQUEST, false);
                    }
                )->assert('contenttypeslug', $this->app['storage']->getContentTypeAssert());
            }
        }
    }

    /**
     * Transforms YML-safe wildcard %% to enclosure in curly braces for symfony
     * routing.
     * Note that scope MUST be public for PHP 5.3 compatibility with closure
     * scope implementation.
     *
     * @param $string
     * @return mixed
     */
    public function transformWildCard($string)
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

    /**
     * Validate routing definitions
     *
     * @param array $routingData
     * @return boolean
     */
    protected function isValidRoutingData($routingData)
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

    /**
     * Create the Twig filter
     *
     * @param string $string
     * @return \Twig_Markup
     */
    public function niceUrlFilter($link)
    {
        // path will have app['paths']['root'] prepended, so remove it
        $url = substr($link, strlen($this->app['paths']['root']));

        // extract routing data
        $parts = explode('/', $url);
        $contentTypeSlug = isset($parts[0]) ? $parts[0] : '';
        $slug = isset($parts[1]) ? $parts[1] : '';

        // lookup routing
        foreach ($this->config as $routingData) {
            if ($this->isValidRoutingData($routingData)) {

                // contenttypeslug must match
                if ($routingData['to']['contenttypeslug'] == $contentTypeSlug) {

                    // look if slug matches (i.e. "page/about")
                    if ($routingData['to']['slug'] == $slug) {
                        $link = $this->app['paths']['root'].$routingData['from']['slug'];
                        break;
                    }

                    // look if slug is parameterized (i.e. "page/%%slug%%")
                    if (strpos($routingData['to']['slug'], '%%') !== false) {
                        // is a parameterized slug,
                        // so replace the "to" parameter with the real slug to get the "from" slug
                        $fromSlug = str_replace($routingData['to']['slug'], $slug, $routingData['from']['slug']);
                        $link = $this->app['paths']['root'].$fromSlug;
                        break;
                    }
                }
            }
        }

        return new \Twig_Markup($link, 'UTF-8');
    }
}
