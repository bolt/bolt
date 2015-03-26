<?php

namespace Bolt\Database\Migration;

use Bolt\Application;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Abstract base class for database import/export
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractMigration
{
    /** @var \Bolt\Application */
    protected $app;

    /** @var \Symfony\Component\Filesystem\Filesystem */
    protected $fs;

    /** @var boolean */
    protected $error = false;

    /** @var array */
    protected $errorMessages = array();

    /** @var array */
    protected $validExtensions = array('json', 'yaml', 'yml');

    /** @var string */
    protected $files;

    /**
     * Constructor.
     *
     * @param \Bolt\Application $app
     * @param string            $files
     * @param boolean           $exists
     */
    public function __construct(Application $app, $files, $exists)
    {
        $this->app = $app;
        $this->fs  = new Filesystem();

        $this->isMigrationFilesValid($files, $exists);
    }

    /**
     * Get the error state.
     *
     * @return boolean
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Set the error state.
     *
     * @param boolean $error
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Get the error messages.
     *
     * @return array
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * Add an error message to the error queue.
     *
     * @param string $errorMessage
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function setErrorMessage($errorMessage)
    {
        $this->errorMessages[] = $errorMessage;

        return $this;
    }

    /**
     * Determine if file(s) specified exist and have a valid extension
     *
     * @param string|array $files  File(s) to check
     * @param boolean      $exists If true, then test that the file exists
     *
     * @return boolean
     */
    private function isMigrationFilesValid($files, $exists = false)
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                return $this->isMigrationFileValid($file);
            }
        }

        $hash = md5($files);

        // Get the file extension and check existace if required
        if ($exists) {
            // Check the file exists
            try {
                $fileObj = new File($files);
                $ext = $fileObj->getExtension();
            } catch (FileNotFoundException $e) {
                $this->setError(true)->setErrorMessage("File '$file' not found!");
            }
        } else {
            $fileObj = new \SplFileInfo($files);
            $ext = $fileObj->getExtension();
        }

        // Check the file extension
        if (!in_array($ext, $this->validExtensions)) {
            $this->setError(true)->setErrorMessage("File '$files' has an invalid extension! Must be either '.json', '.yml' or '.yaml'.");
        } else {
            if ($ext === 'yml' || $ext === 'yaml') {
                $type = 'yaml';
            } elseif ($ext === 'json') {
                $type = 'json';
            }
            $this->files[$hash] = array('file' => $files, 'type' => $type);
        }

        return $this;
    }

    /**
     * Determine if file(s) specified can be writen to.
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function isMigrationFilesWriteable()
    {
        foreach ($this->files as $file) {
            try {
                $this->fs->touch($file['file']);
                $this->fs->remove($file['file']);
            } catch (IOException $e) {
                $this->setError(true)->setErrorMessage("File '{$file['file']}' is not writeable!");
            }
        }

        return $this;
    }
}
