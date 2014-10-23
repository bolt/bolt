<?php

namespace Bolt;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony\Component\Routing\RequestContext;

class BoltUrlMatcher implements UrlMatcherInterface
{
    protected $wrapped;

    public function __construct(UrlMatcherInterface $wrapped)
    {
        $this->wrapped = $wrapped;
    }

    public function match($path)
    {
        try {
            return $this->wrapped->match($path);
        } catch (ResourceNotFoundException $notFound) {
            if ('/' === substr($path, -1)) {
                $withoutTrailingSlash = substr($path, 0, -1);

                try {
                    // See if an exception is thrown matching the URL omitting the trailing slash
                    $this->wrapped->match($withoutTrailingSlash);
                    // Success! Redirect to the URL omitting the trailing slash.
                    return $this->redirect($withoutTrailingSlash);
                } catch (\Exception $e) {
                }
            } else {
                $withTrailingSlash = $path . '/';

                try {
                    // See if an exception is thrown matching the URL including a trailing slash.
                    $this->wrapped->match($withTrailingSlash);
                    // Success! Redirect to the URL including a trailing slash.
                    return $this->redirect($withTrailingSlash);
                } catch (\Exception $e) {
                }
            }

            // If nothing worked, throw the original ResourceNotFoundException
            throw $notFound;
        }
    }

    /**
     * @see Symfony\Component\Routing\RequestContextAwareInterface::setContext()
     */
    public function setContext(RequestContext $context)
    {
        $this->wrapped->setContext($context);
    }

    /**
     * @see Symfony\Component\Routing\RequestContextAwareInterface::getContext()
     */
    public function getContext()
    {
        return $this->wrapped->getContext();
    }

    protected function redirect($path)
    {
        $url = $this->getContext()->getBaseUrl() . $path;

        $query = $this->getContext()->getQueryString() ?: '';
        if ($query !== '') {
            $url .= '?' . $query;
        }

        return array(
            '_controller' => function ($url) {
                return new RedirectResponse($url, 301);
            },
            '_route'      => null,
            'url'         => $url,
        );
    }
}
