<?php

namespace Bolt\Database\Migration;

use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Database records export class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Export extends AbstractMigration
{
    /** @var array */
    private $contenttypes = array();

    /** @var string */
    private $hash;

    /**
     * Export set Contenttype's records to the export file.
     *
     * @return \Bolt\Database\Migration\Export
     */
    public function exportContenttypesRecords()
    {
        if ($this->getError()) {
            return $this;
        }

        // Keep track of our export progress as some data formats require closing elements
        $last = false;
        $end  = array_keys($this->contenttypes);
        $end  = end($end);

        foreach ($this->contenttypes as $key => $contenttype) {
            if ($key === $end) {
                $last = true;
            }

            $this->exportContenttypeRecords($contenttype, $last);
        }

        return $this;
    }

    /**
     * Set the migration files.
     *
     * Also creates an output file object.
     *
     * @see \Bolt\Database\Migration\AbstractMigration::setMigrationFiles()
     *
     * @return \Bolt\Database\Migration\Export
     */
    public function setMigrationFiles($files)
    {
        parent::setMigrationFiles($files);

        if ($this->getError()) {
            return $this;
        }

        $this->hash = md5($files);
        $file = &$this->files[$this->hash];

        if ($file['type'] === 'yaml') {
            $file['handler'] = new Output\YamlFile($this, $file['file']);
        } elseif ($file['type'] === 'json') {
            $file['handler'] = new Output\JsonFile($this, $file['file']);
        }

        return $this;
    }

    /**
     * Export a single Contenttype's records to the export file.
     *
     * @param string  $contenttype
     * @param boolean $last        Flag that indicates last contenttype
     *
     * @return void
     */
    private function exportContenttypeRecords($contenttype, $last)
    {
        // Get all the records for the contenttype
        $records = $this->app['storage']->getContent($contenttype);
        $data = array();

        // If we're on the last Contenttype, we want to know when we've got the
        // last record so we can close off if need be
        if ($last) {
            $last = false;
            $end  = array_keys($records);
            $end  = end($end);
        }

        foreach ($records as $key => $record) {
            if ($key === $end) {
                $last = true;
            }

            $values = $record->getValues();
            unset($values['id']);
            $data[$contenttype] = $values;

            $this->writeMigrationFile($data, $last, true);
        }
    }

    /**
     * Check Contenttype requested exists.
     *
     * @param string|array $contenttypeslugs
     *
     * @return \Bolt\Database\Migration\Export
     */
    public function checkContenttypeValid($contenttypeslugs = array())
    {
        // If nothing is passed in, we assume we're using all conenttypes
        if (empty($contenttypeslugs)) {
            $this->contenttypes = $this->app['storage']->getContentTypes();

            if (empty($this->contenttypes)) {
                $this->setError(true)->setErrorMessage('This installation of Bolt has no contenttypes configured!');
            }

            return $this;
        }

        if (is_array($contenttypeslugs)) {
            foreach ($contenttypeslugs as $contenttypeslug) {
                return $this->checkContenttypeValid($contenttypeslug);
            }
        }

        $contenttype = $this->app['storage']->getContentType($contenttypeslugs);

        if (empty($contenttype)) {
            $this->setError(true)->setErrorMessage("The requested Contenttype '$contenttypeslugs' doesn't exist!");
        } elseif (!isset($this->contenttypes[$contenttypeslugs])) {
            $this->contenttypes[] = $contenttypeslugs;
        }

        return $this;
    }

    /**
     * Write a migration file.
     *
     * This function will determine what type based on extension.
     *
     * @param array   $data   The data to write out
     * @param boolean $last   Flag that indicates last record
     * @param boolean $append Whether to append or abort file writing if a file exists
     *
     * @return array
     */
    protected function writeMigrationFile($data, $last, $append = false)
    {
        $file = (string) $this->files[$this->hash]['file'];

        if ($this->fs->exists($file) && $append === false) {
            $this->setError(true)->setErrorMessage("Specified file '$file' already exists!");

            return false;
        }

        try {
            $this->fs->touch($file);
        } catch (IOException $e) {
            $this->setError(true)->setErrorMessage("Specified file '$file' can not be created!");

            return false;
        }

        // Write them out
        return $this->files[$this->hash]['handler']->addRecord($data, $last);
    }
}
