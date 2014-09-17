<?php
namespace Bolt\Configuration;

use Bolt\Application;
use Symfony\Component\HttpFoundation\Request;
use Composer\Autoload\ClassLoader;

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
        $this->setPath("config", $this->root . '/config');
        $this->setPath("cache", $this->root . '/cache');
        $this->setPath("database", $this->root . '/database');
        $this->setPath("extensions", $this->root . '/extensions');
        $this->setPath("apppath", $this->getPath('root') . '/vendor/bolt/bolt/app');
        $this->setPath("themebase", $this->getPath('app') . '/../theme');
        $this->setPath("extensionsconfig", $this->getPath('config') . "/extensions");

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

    public function useLoader(ClassLoader $loader)
    {
        $this->classLoader = $loader;
        $app = dirname($loader->findFile('Bolt\\Application'));
        $this->root = realpath($app . '/../../../../../../');
    }
}
