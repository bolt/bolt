<?php

namespace Bolt\Filesystem;

use Bolt\Library as Lib;
use Silex\Application;

/**
 * Use to check if an access to a file is allowed.
 *
 * @author Benjamin Georgeault <benjamin@wedgesama.fr>
 */
class FilePermissions
{
    /** @var \Bolt\Application */
    protected $app;

    /** @var string[] List of Filesystem prefixes that are editable. */
    protected $allowedPrefixes = array();

    /** @var array Regex list represented editable resources. */
    protected $allowed = array();

    /** @var array Regex list represented resources forbidden for edition. */
    protected $blocked = array();

    /** @var int Maximum upload size allowed by PHP, in bytes. */
    protected $maxUploadSize;

    /**
     * Constructor, initialize filters rules.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->allowedPrefixes = array(
            'config',
            'files',
            'theme',
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
     * @param string $prefix
     * @param string $path
     *
     * @return bool
     */
    public function authorized($prefix, $path)
    {
        // Check blocked resources
        foreach ($this->blocked as $rule) {
            if (preg_match($rule, $path)) {
                return false;
            }
        }

        // Check allowed filesystems
        foreach ($this->allowedPrefixes as $allowedPrefix) {
            if ($allowedPrefix === $prefix) {
                return true;
            }
        }

        // Check allowed resources
        foreach ($this->allowed as $rule) {
            if (preg_match($rule, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if a given file is acceptable for upload.
     *
     * @param string $originalFilename
     *
     * @return bool
     */
    public function allowedUpload($originalFilename)
    {
        // no UNIX-hidden files
        if ($originalFilename[0] === '.') {
            return false;
        }
        // only whitelisted extensions
        $extension = strtolower(Lib::getExtension($originalFilename));
        $allowedExtensions = $this->getAllowedUploadExtensions();

        return (in_array($extension, $allowedExtensions));
    }

    /**
     * Get the array of configured acceptable file extensions.
     *
     * @return array
     */
    public function getAllowedUploadExtensions()
    {
        return $this->app['config']->get('general/accept_file_types');
    }

    /**
     * Get the maximum upload size the server is configured to accept.
     *
     * @return integer
     */
    public function getMaxUploadSize()
    {
        if (!isset($this->maxUploadSize)) {
            $size = Lib::filesizeToBytes(ini_get('post_max_size'));

            $uploadMax = Lib::filesizeToBytes(ini_get('upload_max_filesize'));
            if (($uploadMax > 0) && ($uploadMax < $size)) {
                $size = $uploadMax;
            }

            $this->maxUploadSize = $size;
        }

        return $this->maxUploadSize;
    }

    /**
     * Get the max upload value in a formatted string.
     *
     * @return string
     */
    public function getMaxUploadSizeNice()
    {
        return Lib::formatFilesize($this->getMaxUploadSize());
    }
}
