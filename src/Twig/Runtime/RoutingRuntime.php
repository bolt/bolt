<?php

namespace Bolt\Twig\Runtime;

use Bolt\Library;
use Bolt\Routing\Canonical;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Bolt specific Twig functions and filters that provide routing functionality.
 *
 * @internal
 */
class RoutingRuntime
{
    /** @var Canonical */
    private $canonical;
    /** @var RequestStack */
    private $requestStack;
    /** @var string */
    private $locale;

    /**
     * Constructor.
     *
     * @param Canonical    $canonical
     * @param RequestStack $requestStack
     * @param string       $locale
     */
    public function __construct(Canonical $canonical, RequestStack $requestStack, $locale)
    {
        $this->canonical = $canonical;
        $this->requestStack = $requestStack;
        $this->locale = $locale;
    }

    /**
     * Get canonical url for current request.
     *
     * @return string|null
     */
    public function canonical()
    {
        return $this->canonical->getUrl();
    }

    /**
     * Returns the language value for in tags where the language attribute is
     * required. The underscore '_' in the locale will be replaced with a
     * hyphen '-'.
     *
     * @return string
     */
    public function htmlLang()
    {
        $request = $this->requestStack->getCurrentRequest();
        $locale = $request ? $request->getLocale() : $this->locale;

        return str_replace('_', '-', $locale);
    }

    /**
     * Check if the page is viewed on a mobile device.
     *
     * @return boolean
     */
    public function isMobileClient()
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return false;
        }

        return preg_match(
            '/(android|blackberry|htc|iemobile|iphone|ipad|ipaq|ipod|nokia|playbook|smartphone)/i',
            $request->headers->get('User-Agent')
        );
    }

    /**
     * Redirect the browser to another page.
     *
     * @param string $path
     *
     * @return string
     */
    public function redirect($path)
    {
        Library::simpleredirect($path);

        return '';
    }

    /**
     * Return the requested parameter from $_REQUEST, $_GET or $_POST.
     *
     * @param string  $parameter    The parameter to get
     * @param string  $from         'GET' or 'POST', all the others falls back to REQUEST.
     * @param boolean $stripSlashes Apply stripslashes. Defaults to false.
     *
     * @return mixed
     */
    public function request($parameter, $from = '', $stripSlashes = false)
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return null;
        }

        $from = strtoupper($from);

        if ($from === 'GET') {
            $value = $request->query->get($parameter, false);
        } elseif ($from === 'POST') {
            $value = $request->request->get($parameter, false);
        } else {
            $value = $request->get($parameter, false);
        }

        if ($stripSlashes) {
            $value = stripslashes($value);
        }

        return $value;
    }
}
