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
     * Import each migration file
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

    /**
     * Import records from an import file
     *
     * @param string $filename
     *
     * @return boolean
     */
    private function importRecords($filename)
    {
        foreach ($this->data as $data) {
            // Test that we've at the least of an array
            if (!is_array($data)) {
                $this->setError(true)->setErrorMessage("File '$filename' has malformed Contenttype import data! Skipping file.");
                return false;
            }

            // Validate all the contenttypes in this file
            foreach ($data as $contenttypeslug => $values) {
                if (!$this->checkContenttypesValid($filename, $contenttypeslug)) {
                    return false;
                }
            }

            // Insert the record
            foreach ($data as $contenttypeslug => $values) {
                $this->insertRecord($filename, $contenttypeslug, $values);
            }
        }
    }

    /**
     * Check that the Contenttype specified in the record data is valid.
     *
     * @param string $filename
     * @param string $contenttypeslug
     *
     * @return
     */
    private function checkContenttypesValid($filename, $contenttypeslug)
    {
        if (isset($this->contenttypes[$contenttypeslug])) {
            return true;
        }

        $contenttype = $this->app['storage']->getContentType($contenttypeslug);

        if (empty($contenttype)) {
            $this->setError(true)->setErrorMessage("File '$filename' has and invalid Contenttype '$contenttypeslug'! Skipping file.");

            return false;
        }

        $this->contenttypes[$contenttypeslug] = $contenttype;

        return true;
    }
}
