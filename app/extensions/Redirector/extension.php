<?php
// Redirector Extension 0.3 for Bolt
// Minimum Bolt version: 1.2

namespace Redirector;

use Bolt\BaseExtension as BoltExtension;
use Silex\Application as Application;
use Symfony\Component\HttpFoundation\Request as Request;

class extension extends BoltExtension
{
    public $placeholders = array(
        ':all' => '.*',
        ':alpha' => '[a-z]+',
        ':alphanum' => '[a-z0-9]+',
        ':any' => '[a-z0-9\.\-\_\%\=\s]+',
        ':num' => '[0-9]+',
        ':segment' => '[a-z0-9\-\_]+',
        ':segments' => '[a-z0-9\-\_\/]+',
        ':ext' => 'aspx?|f?cgi|s?html?|jhtml|jsp|phps?', // any more to add? doubt it though...
    );

    public $computedReplacements = array();

    public $source;
    public $destination;

    /**
     * Basic information about the extension. Shown in the Bolt Admin Environment.
     *
     * @return array
     */

    public function info()
    {
        $data = array(
            'name' => 'Redirector',
            'version' => '0.3',
            'author' => 'Foundry Code - Mike Anthony',
            'description' => 'An extension that allows you to perform any pre-app <code>301 Moved Permanently</code> redirects.',
            'type' => 'General',
            'link' => 'http://foundry-code.github.io/bolt-redirector',
            'first_releasedate' => '2013-09-10',
            'latest_releasedate' => '2013-09-12',
            'required_bolt_version' => '1.2',
            'highest_bolt_version' => '1.2'
        );

        return $data;
    }

    /**
     * Initialise the extension's functions
     *
     * @return void
     */

    public function initialize()
    {
        $this->options = $this->config['options'];
        $this->redirects = $this->config['redirects'];
        $this->handleRedirects();
    }

    /**
     * Make input slugs more friendly. Like cats.
     *
     * @param string $input
     * @return string
     */
    public function slugify($input)
    {
        $input = preg_replace('~[^\\pL\d\/]+~u', '-', $input);
        $input = trim($input, '-');
        $input = iconv('utf-8', 'us-ascii//TRANSLIT', $input);
        $input = strtolower($input);
        $input = preg_replace('~[^-\w\/]+~', '', $input);
        if (empty($input)) {
            return '';
        }

        // Just a temporary fix for the slugifier... Have tried many things, but to no avail.
        return str_replace('-20', '-', $input);
    }

    /**
     * Check for a redirect. If it exists, then redirect to it's
     * converted replacement.
     *
     * @return RedirectResponse | void
     */
    public function handleRedirects()
    {
        $redirector = $this;
        $app = $this->app;

        $app->before(function (Request $request) use ($redirector, $app) {
            if (empty($redirector->redirects)) {
                return;
            }
            $requestUri = trim($request->getRequestUri(), '/');

            $availablePlaceholders = '';
            foreach ($redirector->placeholders as $placeholder => $expression) {
                $availablePlaceholders .= ltrim("$placeholder|", ':');
            }
            $availablePlaceholders = rtrim($availablePlaceholders, '|');

            $pattern = '#\{(\w+):(' . $availablePlaceholders . ')\}#';

            foreach ($redirector->redirects as $redirectName => $redirectData) {
                $redirector->computedReplacements = array();
                $redirector->source = trim($redirectData['from'], '/');
                $redirector->destination = trim($redirectData['to'], '/');

                $convertedPlaceholders = preg_replace_callback($pattern, function ($captures) use ($redirector) {
                    $redirector->computedReplacements[] = $captures[1];

                    return '(' . $redirector->placeholders[":{$captures[2]}"] . ')';
                }, $redirector->source);

                if (preg_match("#^$convertedPlaceholders$#i", $requestUri)) {
                    $convertedReplacements = preg_replace_callback("#^$convertedPlaceholders$#i", function ($captures) use ($redirector) {
                        $result = $redirector->destination;
                        for ($c = 1, $n = count($captures); $c < $n; ++$c) {
                            $value = array_shift($redirector->computedReplacements);
                            if ($redirector->options['autoslug']) {
                                $captures[$c] = $redirector->slugify($captures[$c]);
                            }
                            $result = str_replace('{' . $value . '}', $captures[$c], $result);
                        }

                        return $result;
                    }, $requestUri);

                    return $app->redirect(strtolower("/$convertedReplacements"), 301);
                }
            }
        }, Application::EARLY_EVENT);
    }
}
