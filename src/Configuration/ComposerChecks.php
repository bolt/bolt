<?php
namespace Bolt\Configuration;

use Bolt\Exception\LowlevelException;

/**
 * Inherits from default and adds some specific checks for composer installs.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 **/

class ComposerChecks extends LowlevelChecks
{
    public $composerSuffix = <<<HTML
    </strong></p><p>When using Bolt as a Composer package it will need to have access to the following folders:</p>
    <ol>
        <li class="status-%s">A writable config directory at: <code>%s</code></li>
        <li class="status-%s">For a default SQLite install, a writable directory at: <code>%s</code></li>
        <li class="status-%s">A writable cache directory at: <code>%s</code></li>
        <li class="status-%s">A writable extensions directory at: <code>%s</code></li>
        <li class="status-%s">A writable extensions assets directory at: <code>%s</code></li>
    </ol>
    <p>If any of the above are failing, create the folder and make it writable to the web server.</p>
    <strong>
HTML;

    /**
     * The constructor requires a resource manager object to perform checks against.
     * This should ideally be typehinted to Bolt\Configuration\ResourceManager.
     *
     * @param ResourceManager $config
     */
    public function __construct($config = null)
    {
        parent::__construct($config);
        $this->addCheck('publicAssets');
        $this->addCheck('database', true);
        $this->addCheck('config', true);
    }

    public function checkConfig()
    {
        $this->checkDir($this->config->getPath('config'));
    }

    public function checkCache()
    {
        $this->checkDir($this->config->getPath('cache'));
    }

    public function checkDatabase()
    {
        $this->checkDir($this->config->getPath('database'));
    }

    public function checkPublicAssets()
    {
        $this->checkDir($this->config->getPath('web') . '/extensions');
    }

    protected function checkSummary()
    {
        $status = array();
        $status[] = $this->composerSuffix;
        $checks = array(
            $this->config->getPath('config'),
            $this->config->getPath('database'),
            $this->config->getPath('cache'),
            $this->config->getPath('extensions'),
            $this->config->getPath('web') . '/extensions'
        );
        foreach ($checks as $check) {
            if (is_readable($check) && is_writable($check)) {
                $status[] = 'ok';
                $status[] = $check;
            } else {
                $status[] = 'error';
                $status[] = $check;
            }
        }

        return call_user_func_array('sprintf', $status);
    }

    public function checkDir($location)
    {
        // As a last resort we can try to create the directory here:
        if (!is_dir($location)) {
            @mkdir($location, 0777, true);
        }

        if (!is_dir($location)) {
            throw new LowlevelException(
                "The default folder <code>" . $location .
                "</code> doesn't exist. Make sure it's " .
                'present and writable to the user that the webserver is using.' . $this->checkSummary()
            );
        } elseif (!is_writable($location)) {
            throw new LowlevelException(
                "The default folder <code>" . $location .
                "</code> isn't writable. Make sure it's writable to the user that the webserver is using." . $this->checkSummary()
            );
        }
    }
}
