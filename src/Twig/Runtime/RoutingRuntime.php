<?php

namespace Bolt\Twig\Runtime;

use Bolt\Library;
use Bolt\Routing\Canonical;
use Bolt\Users;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    /** @var UrlGeneratorInterface  */
    private $urlGenerator;

    /** @var Users  */
    private $users;

    /**
     * Constructor.
     *
     * @param Canonical             $canonical
     * @param RequestStack          $requestStack
     * @param string                $locale
     * @param UrlGeneratorInterface $urlGenerator
     * @param Users                 $users
     */
    public function __construct(
        Canonical $canonical,
        RequestStack $requestStack,
        $locale,
        UrlGeneratorInterface $urlGenerator,
        Users $users
    ) {
        $this->canonical = $canonical;
        $this->requestStack = $requestStack;
        $this->locale = $locale;
        $this->urlGenerator = $urlGenerator;
        $this->users = $users;
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
     * Generate a backend edit link for a given record if the user has the permission
     *
     * @param $record
     *
     * @return bool
     */
    public function editlink($record)
    {
        $perm = 'contenttype:' . $record->contenttype['slug'] . ':edit:' . $record->id;

        if ($this->users->getCurrentUser() && $this->users->isAllowed($perm)) {
            return $this->urlGenerator->generate('editcontent', ['contenttypeslug' => $record->contenttype['slug'], 'id' => $record->id]);
        }

        return false;
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
     * @return bool
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
     * @param string $parameter    The parameter to get
     * @param string $from         'GET' or 'POST', all the others falls back to REQUEST
     * @param bool   $stripSlashes Apply stripslashes. Defaults to false.
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
