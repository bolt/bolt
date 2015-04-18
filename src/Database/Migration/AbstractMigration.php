<?php

namespace Bolt\Database\Migration;

use Bolt\Application;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

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

    /** @var boolean */
    protected $warning = false;

    /** @var array */
    protected $warningMessages = array();

    /** @var boolean */
    protected $notice = false;

    /** @var array */
    protected $noticeMessages = array();

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
     * Get the warning state.
     *
     * @return boolean
     */
    public function getWarning()
    {
        return $this->warning;
    }

    /**
     * Set the warning state.
     *
     * @param boolean $warning
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function setWarning($warning)
    {
        $this->warning = $warning;

        return $this;
    }

    /**
     * Get the warning messages.
     *
     * @return array
     */
    public function getWarningMessages()
    {
        return $this->warningMessages;
    }

    /**
     * Add an warning message to the warning queue.
     *
     * @param string $warningMessage
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function setWarningMessage($warningMessage)
    {
        $this->warningMessages[] = $warningMessage;

        return $this;
    }

    /**
     * Get the notice state.
     *
     * @return boolean
     */
    public function getNotice()
    {
        return $this->notice;
    }

    /**
     * Set the notice state.
     *
     * @param boolean $notice
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function setNotice($notice)
    {
        $this->notice = $notice;

        return $this;
    }

    /**
     * Get the notice messages.
     *
     * @return array
     */
    public function getNoticeMessages()
    {
        return $this->noticeMessages;
    }

    /**
     * Add an notice message to the notice queue.
     *
     * @param string $noticeMessage
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function setNoticeMessage($noticeMessage)
    {
        $this->noticeMessages[] = $noticeMessage;

        return $this;
    }

    /**
     * Set the migration files.
     *
     * @param string|array $files File(s)
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
            'file'    => $fileObj,
            'type'    => $this->getType($fileObj->getExtension()),
            'handler' => null
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

    /**
     * Test if the migration files are in an appropriate state for the migration
     * type.
     *
     * @param string $migration Type of migration, either 'import' or 'export'
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function checkMigrationFilesExist($migration)
    {
        if ($this->getError()) {
            return $this;
        }

        foreach ($this->files as $file) {
            $filename = (string) $file['file'];

            if ($this->fs->exists($filename) && $migration === 'export') {
                $this->setError(true)->setErrorMessage("File '$filename' exists.");
            } elseif (!$this->fs->exists($filename) && $migration === 'import') {
                $this->setError(true)->setErrorMessage("File '$filename' does not exist.");
            }
        }

        return $this;
    }

    /**
     * Determine if file(s) specified exist and have a valid extension
     *
     * @param boolean $exists If true, then test that the file exists
     *
     * @return \Bolt\Database\Migration\AbstractMigration
     */
    public function checkMigrationFilesValid($exists = false)
    {
        if ($this->getError()) {
            return $this;
        }

        foreach ($this->files as $file) {
            $filename = (string) $file['file'];

            // Get the file extension and check existace if required
            if ($exists) {
                // Check the file exists
                try {
                    new File($filename);
                } catch (FileNotFoundException $e) {
                    $this->setError(true)->setErrorMessage("File '$filename' not found!");
                }
            }

            // Check the file extension
            if (!in_array($file['type'], $this->validExtensions)) {
                $this->setError(true)->setErrorMessage("File '$filename' has an invalid extension! Must be either '.json', '.yml' or '.yaml'.");
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
            $filename = (string) $file['file'];

            try {
                $this->fs->touch($filename);
                $this->fs->remove($filename);
            } catch (IOException $e) {
                $this->setError(true)->setErrorMessage("File '$filename' is not writeable!");
            }
        }

        return $this;
    }
}
