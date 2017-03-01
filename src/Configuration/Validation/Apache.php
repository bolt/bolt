<?php

namespace Bolt\Configuration\Validation;

use Bolt\Configuration\PathResolver;
use Bolt\Exception\Configuration\Validation\System\ApacheValidationException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Apache .htaccess validation check.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Apache implements ValidationInterface, PathResolverAwareInterface
{
    /** @var PathResolver */
    private $pathResolver;

    /**
     * This check looks for the presence of the .htaccess file inside the web directory.
     * It is here only as a convenience check for users that install the basic version of Bolt.
     *
     * {@inheritdoc}
     */
    public function check()
    {
        $request = Request::createFromGlobals();
        $serverSoftware = $request->server->get('SERVER_SOFTWARE', '');
        $isApache = strpos($serverSoftware, 'Apache') !== false;
        if (!$isApache) {
            return;
        }

        $path = $this->pathResolver->resolve('%web%/.htaccess');
        if (is_readable($path)) {
            return;
        }

        throw new ApacheValidationException();
    }

    /**
     * {@inheritdoc}
     */
    public function setPathResolver(PathResolver $pathResolver)
    {
        $this->pathResolver = $pathResolver;
    }
}
