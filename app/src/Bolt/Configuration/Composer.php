<?php
namespace Bolt\Configuration;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;

class Composer extends Standard
{

    /**
     * Constructor initialises on the app root path.
     *
     * @param string $path
     */
    public function __construct($loader, Request $request = null)
    {
        parent::__construct($loader, $request);
        $this->setPath("composer", $this->root);
        $this->setUrl("app", "/bolt-public/");
    }

    public function compat()
    {
        if (! defined("BOLT_COMPOSER_INSTALLED")) {
            define('BOLT_COMPOSER_INSTALLED', true);
        }
        parent::compat();
    }

    public function getVerifier()
    {
        if (! $this->verifier) {
            $this->verifier = new ComposerChecks($this);
        }

        return $this->verifier;
    }

    
}
