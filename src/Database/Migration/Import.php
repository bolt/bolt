<?php

namespace Bolt\Database\Migration;

use Bolt\Helpers\Arr;
use Symfony\Component\Yaml\Parser;

/**
 * Database records iport class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Import extends AbstractMigration
{
    /**
     * Set the migration files.
     *
     * Also creates an input file objects.
     *
     * @see \Bolt\Database\Migration\AbstractMigration::setMigrationFiles()
     */
    public function setMigrationFiles($files)
    {
        parent::setMigrationFiles($files);

        if ($this->getError()) {
            return $this;
        }

        foreach ($this->files as &$file) {
            if ($file['type'] === 'yaml') {
                $file['handler'] = new Input\YamlFile($this, $file['file']);
            } elseif ($file['type'] === 'json') {
                $file['handler'] = new Input\JsonFile($this, $file['file']);
            }
        }

        return $this;
    }

    /**
     *
     * @return \Bolt\Database\Migration\Import
     */
    public function importMigrationFiles()
    {
        if ($this->getError()) {
            return $this;
        }

        foreach ($this->files as $file) {
            $file['output']->readFile();
            $filename = (string) $file['file'];

            // Our import arrays should be indexed, if not we have a problem
            if (!Arr::isIndexedArray($this->data)) {
                $this->setError(true)->setErrorMessage("File '$filename' has an invalid import format!");
                continue;
            }

            // Import the records from the given file
            $this->importRecords($filename);
        }

        return $this;
    }
}
