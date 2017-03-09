<?php

namespace Bolt\Twig\Handler;

use Silex;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Bolt specific Twig functions and filters that provide user functionality
 *
 * @internal
 */
class UserHandler
{
    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Get an array of data for a user, based on the given name, email address,
     * or ID. Returns an array on success, and false otherwise.
     *
     * @param mixed $who
     *
     * @return mixed
     */
    public function getUser($who)
    {
        return $this->app['users']->getUser($who);
    }

    /**
     * Get an id number for a user, based on the given name. Returns
     * an integer id on success, and false otherwise.
     *
     * @param string $who
     *
     * @return mixed
     */
    public function getUserId($who)
    {
        $user = $this->app['users']->getUser($who);

        if (isset($user['id'])) {
            return $user['id'];
        } else {
            return false;
        }
    }

    /**
     * Check if a certain action is allowed for the current user (and possibly
     * content item).
     *
     * @param string $what    Operation
     * @param mixed  $content If specified, a Content item.
     *
     * @return boolean True if allowed
     */
    public function isAllowed($what, $content = null)
    {
        $contenttype = null;
        $contentid = null;
        if ($content instanceof \Bolt\Legacy\Content) {
            // It's a Legacy content record
            $contenttype = $content;
            $contentid = $content['id'];
        } elseif ($content instanceof \Bolt\Storage\Mapping\ContentType) {
            // It's non-legacy content record.
            $contenttype = (string) $content;
        } elseif (is_array($content)) {
            // It's a contenttype
            $contenttype = $content;
        } elseif (is_string($content)) {
            $contenttype = $content;
        }

        return $this->app['users']->isAllowed($what, $contenttype, $contentid);
    }

    /**
     * Get a simple Anti-CSRF-like token.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use Symfony forms instead.
     *
     * @return CsrfToken
     */
    public function token()
    {
        return $this->app['csrf']->getToken('bolt');
    }
}
