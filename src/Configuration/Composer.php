<?php

namespace Bolt\Configuration;

use Bolt\Common\Deprecated;
use Symfony\Component\HttpFoundation\Request;

/**
 * Configuration for a Bolt application Composer install.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Composer extends Standard
{
    /**
     * Constructor initialises on the app root path.
     *
     * @param string              $path
     * @param Request             $request
     * @param PathResolverFactory $pathResolverFactory
     */
    public function __construct($path, Request $request = null, PathResolverFactory $pathResolverFactory = null)
    {
        parent::__construct($path, $request, $pathResolverFactory);
        $this->setPath('composer', realpath(dirname(__DIR__) . '/../'), false);
        $this->setPath('view', '%web%/bolt-public/view');
        $this->setPath('web', 'public');
        $this->setUrl('app', '/bolt-public/');
        $this->setUrl('view', '/bolt-public/view/');
    }

    public function getVerifier()
    {
        Deprecated::method(3.3);

        if (!$this->verifier) {
            $this->verifier = new ComposerChecks($this);
        }

        return $this->verifier;
    }
}
