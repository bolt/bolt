<?php

namespace Bolt\Storage\Migration;

use Bolt\Version;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Database records export class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Export extends AbstractMigration
{
    /** @var array */
    private $contentTypes = [];
    /** @var string */
    private $hash;

    /**
     * Export the meta information to the export file.
     */
    public function exportMetaInformation()
    {
        $data = [
            '__bolt_meta_information' => [
                'date_generated'    => date('Y-m-d H:i:s'),
                'bolt_version'      => Version::VERSION,
                'database_platform' => $this->app['db']->getDatabasePlatform()->getName(),
            ],
        ];
        $this->writeMigrationFile($data, false, true);

        return $this;
    }

    /**
     * Export set ContentType's records to the export file.
     *
     * @return \Bolt\Storage\Migration\Export
     */
    public function exportContentTypesRecords()
    {
        if ($this->getError()) {
            return $this;
        }

        // Keep track of our export progress as some data formats require closing elements
        $last = false;
        $end  = array_keys($this->contentTypes);
        $end  = end($end);

        foreach ($this->contentTypes as $key => $contenttype) {
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
     * @see \Bolt\Storage\Migration\AbstractMigration::setMigrationFiles()
     *
     * @param mixed $files
     *
     * @return \Bolt\Storage\Migration\Export
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
     * Export a single ContentType's records to the export file.
     *
     * @param string  $contenttype
     * @param boolean $last        Flag that indicates last contenttype
     */
    private function exportContenttypeRecords($contenttype, $last)
    {
        // Get all the records for the contenttype
        $records = $this->app['query']->getContent($contenttype);
        $data = [];

        // If we're on the last Contenttype, we want to know when we've got the
        // last record so we can close off if need be
        if ($last) {
            $last = false;
            $end  = count($records) -1;
        } else {
            $end = null;
        }

        foreach ($records as $key => $record) {
            if ($key === $end) {
                $last = true;
            }

            $values = [];
            $repo = $this->app['storage']->getRepository($contenttype);
            $metadata = $repo->getClassMetadata();
            foreach ($metadata->getFieldMappings() as $field) {
                $fieldName = $field['fieldname'];
                $val = $record->$fieldName;
                if (in_array($field['type'], ['date','datetime'])) {
                    $val = (string)$record->$fieldName;
                }
                if (is_callable([$val, 'serialize'])) {
                    $val = $val->serialize();
                }

                $values[$fieldName] = $val;
            }


            unset($values['id']);
            $values['_id'] = $record->getContenttype() . '/' . $record->getSlug();
            $data[$contenttype] = $values;

            $this->writeMigrationFile($data, $last, true);
        }
    }

    /**
     * Check Contenttype requested exists.
     *
     * @param string|array $contenttypeslugs
     *
     * @return \Bolt\Storage\Migration\Export
     */
    public function checkContenttypeValid($contenttypeslugs = [])
    {
        // If nothing is passed in, we assume we're using all conenttypes
        if (empty($contenttypeslugs)) {
            $this->contentTypes = $this->app['storage']->getContentTypes();

            if (empty($this->contentTypes)) {
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
            $this->setError(true)->setErrorMessage("The requested ContentType '$contenttypeslugs' doesn't exist!");
        } elseif (!isset($this->contentTypes[$contenttypeslugs])) {
            $this->contentTypes[] = $contenttypeslugs;
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
