<?php

namespace Bolt\Database\Migration;

use Bolt\Helpers\Arr;

/**
 * Database records iport class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Import extends AbstractMigration
{
    /** @var array $data */
    protected $data;

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
            // Read the file data in
            if (!$file['handler']->readFile()) {
                continue;
            }

            // Get the file name
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
     * Setter for data
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
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
     * @return boolean
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

    /**
     * Insert an individual Contenttype record into the database
     *
     * @param string $filename
     * @param string $contenttypeslug
     * @param array  $values
     *
     * @return boolean
     */
    private function insertRecord($filename, $contenttypeslug, array $values)
    {
        // Determine a/the slug
        $slug = isset($values['slug']) ? $values['slug'] : substr($this->app['slugify']->slugify($values['title']), 0, 127);

        if (!$this->isRecordUnique($contenttypeslug, $slug)) {
            $this->setWarning(true)->setWarningMessage("File '$filename' has an exiting Contenttype '$contenttypeslug' with the slug '$slug'! Skipping record.");

            return false;
        }

        // Get a status
        if (isset($values['status'])) {
            $status = $values['status'];
        } else {
            $status = $this->contenttypes[$contenttypeslug]['default_status'];
        }

        // Transform the 'publish' action to a 'published' status
        $status = $status === 'publish' ? 'published' : $status;

        // Insist on a title field
        if (!isset($values['title'])) {
            $this->setWarning(true)->setWarningMessage("File '$filename' has a '$contenttypeslug' with a missing title field! Skipping record.");

            return false;
        }

        // Set up default meta
        $meta = array(
            'slug'        => $slug,
            'datecreated' => date('Y-m-d H:i:s'),
            'datepublish' => $status == 'published' ? date('Y-m-d H:i:s') : null,
            'ownerid'     => 1
        );

        $values = Arr::mergeRecursiveDistinct($values, $meta);

        $record = $this->app['storage']->getEmptyContent($contenttypeslug);
        $record->setValues($values);

        if ($this->app['storage']->saveContent($record) === false) {
            $this->setWarning(true)->setWarningMessage("Failed to imported record with title: {$values['title']} from '$filename'! Skipping record.");

            return false;
        } else {
            $this->setNotice(true)->setNoticeMessage("Imported record with title: {$values['title']}.");

            return true;
        }
    }

    /**
     * Test is a record already exists.
     *
     * @param string $contenttypeslug
     * @param string $slug
     *
     * @return boolean
     */
    private function isRecordUnique($contenttypeslug, $slug)
    {
        $record = $this->app['storage']->getContent("$contenttypeslug/$slug");
        if (empty($record)) {
            return true;
        }

        return false;
    }
}
