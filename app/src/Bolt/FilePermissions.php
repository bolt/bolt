<?php

namespace Bolt;

/**
 * Use to check if an access to a file is allowed.
 *
 * @author Benjamin Georgeault <benjamin@wedgesama.fr>
 */
class FilePermissions
{
    /**
     * @var \Bolt\Application
     */
    private $app;

    /**
     * Regex list represented editable resources.
     *
     * @var array
     */
    private $allowed;

    /**
     * Regex list represented resources forbidden for edition.
     *
     * @var array
     */
    private $blocked;

    /**
     * Constructor, initialize filters rules.
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->allowed = array(
            '#^' . preg_quote(realpath($app['resources']->getPath('config'))) . '#',
            '#^' . preg_quote(realpath($app['resources']->getPath('extensions'))) . '.*config\.yml$#',
            '#^' . preg_quote(realpath($app['resources']->getPath('files'))) . '#',
            '#^' . preg_quote(realpath($app['resources']->getPath('themebase'))) . '#'
        );

        $this->blocked = array(
            '#.php$#',
            '#\.htaccess#',
            '#\.htpasswd#'
        );
    }

    /**
     * Check if you can do something with the given file or directory.
     *
     * @param $filename
     * @return boolean
     */
    public function authorized($filename)
    {
        $authorized = true;

        // Check blocked resources
        foreach ($this->blocked as $rule) {
            if (preg_match($rule, $filename)) {
                $authorized = false;
                break;
            }
        }

        // Check allowed resources
        if ($authorized) {
            $authorized = false;
            foreach ($this->allowed as $rule) {
                if (preg_match($rule, $filename)) {
                    $authorized = true;
                    break;
                }
            }
        }

        return $authorized;
    }

    /**
     * Checks if a given file is acceptable for upload.
     */
    public function allowedUpload($originalFilename)
    {
        // no UNIX-hidden files
        if ($originalFilename[0] === '.') {
            return false;
        }
        // only whitelisted extensions
        $extension = strtolower(getExtension($originalFilename));
        $allowedExtensions = $this->getAllowedUploadExtensions();

        return (in_array($extension, $allowedExtensions));
    }

    public function getAllowedUploadExtensions()
    {
        return $this->app['config']->get('general/accept_file_types');
    }
}
