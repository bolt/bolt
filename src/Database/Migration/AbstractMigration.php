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
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->fs  = new Filesystem();
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
     * Set the migration files
     *
     * @param string|array $files  File(s)
     * @param boolean      $exists If true, then test that the file exists
     *
     * @return boolean
     */
    public function setMigrationFiles($files)
    {
        if (is_array($files)) {
            foreach ($files as $file) {
                return $this->setMigrationFiles($file);
            }
        }

        if (empty($files)) {
            $this->setError(true)->setErrorMessage('No files given.');

            return $this;
        }

        // Hash to identify the file
        $hash    = md5($files);
        $fileObj = new \SplFileInfo($files);

        $this->files[$hash] = array(
            'file' => $files,
            'type' => $this->getType($fileObj->getExtension())
        );

        return $this;
    }

    /**
     * Get a data type from a file extension
     *
     * @param string $type
     *
     * @return string
     */
    private function getType($type)
    {
        if ($type === 'yml' || $type === 'yaml') {
            return 'yaml';
        } elseif ($type === 'json') {
            return'json';
        }
    }

    public function checkMigrationFilesExist($migration)
    {
        foreach ($this->files as $file) {
            if ($this->fs->exists($file['file']) && $migration === 'export') {
                $this->setError(true)->setErrorMessage("File '{$file['file']}' exists.");
            } elseif (!$this->fs->exists($file) && $migration === 'import') {
                $this->setError(true)->setErrorMessage("File '{$file['file']}' does not exist.");
            }
        }

        return $this;
    }

    /**
     * Determine if file(s) specified exist and have a valid extension
     *
     * @param boolean      $exists If true, then test that the file exists
     *
     * @return boolean
     */
    public function checkMigrationFilesValid($exists = false)
    {
        foreach ($this->files as $file) {
            // Get the file extension and check existace if required
            if ($exists) {
                // Check the file exists
                try {
                    new File($file['file']);
                } catch (FileNotFoundException $e) {
                    $this->setError(true)->setErrorMessage("File '{$file['file']}' not found!");
                }
            }

            // Check the file extension
            if (!in_array($file['type'], $this->validExtensions)) {
                $this->setError(true)->setErrorMessage("File '{$file['file']}' has an invalid extension! Must be either '.json', '.yml' or '.yaml'.");
            }
        }

        return $this;
    }

    /**
     * Determine if file(s) specified can be writen to.
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function checkMigrationFilesWriteable()
    {
        if ($this->getError()) {
            return $this;
        }

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
