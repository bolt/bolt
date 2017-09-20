<?php

namespace Bolt\Storage\Migration;

use Bolt\Collection\Arr;
use Bolt\Storage\Entity\Relations;
use Bolt\Storage\Field\Type\RelationType;
use Bolt\Storage\Repository;

/**
 * Database records import class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Import extends AbstractMigration
{
    /** @var array $data */
    protected $data;
    /** @var array */
    protected $relationQueue = [];
    /** @var bool */
    protected $allowOverwrite = false;
    /** @var array */
    protected $contentTypes = [];

    /**
     * Set the migration files.
     *
     * Also creates an input file objects.
     *
     * @see \Bolt\Storage\Migration\AbstractMigration::setMigrationFiles()
     *
     * @param mixed $files
     *
     * @return \Bolt\Storage\Migration\Import
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
     * Import each migration file.
     *
     * @return \Bolt\Storage\Migration\Import
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
            if (!Arr::isIndexed($this->data)) {
                $this->setError(true)->setErrorMessage("File '$filename' has an invalid import format!");
                continue;
            }

            // Import the records from the given file
            $this->importRecords($filename);
        }

        return $this;
    }

    /**
     * Setter for data.
     *
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function setAllowOverwrite($option)
    {
        $this->allowOverwrite = $option;
    }

    /**
     * Import records from an import file.
     *
     * @param string $filename
     *
     * @return boolean
     */
    protected function importRecords($filename)
    {
        foreach ($this->data as $data) {
            // Test that we've at the least of an array
            if (!is_array($data)) {
                $this->setError(true)->setErrorMessage("File '$filename' has malformed ContentType import data! Skipping file.");

                return false;
            }

            // Validate all the contenttypes in this file
            foreach ($data as $contenttypeslug => $values) {
                // If we have meta information, output it, and continue with the next one.
                if ($contenttypeslug === '__bolt_meta_information') {
                    foreach ($values as $key => $value) {
                        $this->setNotice(true)->setNoticeMessage("Meta information: {$key} = {$value}");
                    }
                    continue 2;
                }

                if (!$this->checkContenttypesValid($filename, $contenttypeslug)) {
                    return false;
                }
            }

            // Insert the record
            foreach ($data as $contenttypeslug => $values) {
                $this->insertRecord($filename, $contenttypeslug, $values);
            }
        }
        $this->processRelationQueue();
    }

    /**
     * Check that the ContentType specified in the record data is valid.
     *
     * @param string $filename
     * @param string $contenttypeslug
     *
     * @return boolean
     */
    protected function checkContentTypesValid($filename, $contenttypeslug)
    {
        if (isset($this->contenttypes[$contenttypeslug])) {
            return true;
        }

        $contenttype = $this->app['storage']->getContentType($contenttypeslug);

        if (empty($contenttype)) {
            $this->setError(true)->setErrorMessage("File '$filename' has and invalid ContentType '$contenttypeslug'! Skipping file.");

            return false;
        }

        $this->contenttypes[$contenttypeslug] = $contenttype;

        return true;
    }

    /**
     * Insert an individual Contenttype record into the database.
     *
     * @param string $filename
     * @param string $contenttypeslug
     * @param array  $values
     *
     * @return boolean
     */
    protected function insertRecord($filename, $contenttypeslug, array $values)
    {
        // Determine a/the slug
        $slug = isset($values['slug']) ? $values['slug'] : substr($this->app['slugify']->slugify($values['title']), 0, 127);

        if (!$this->isRecordUnique($contenttypeslug, $slug)) {
            $this->setWarning(true)->setWarningMessage("File '$filename' has an exiting ContentType '$contenttypeslug' with the slug '$slug'! Skipping record.");

            return false;
        }

        // Get a status
        $status = isset($values['status']) ? $values['status'] : $this->contenttypes[$contenttypeslug]['default_status'];

        // Transform the 'publish' action to a 'published' status
        $status = $status === 'publish' ? 'published' : $status;

        // Insist on a title field
        if (!isset($values['title'])) {
            $this->setWarning(true)->setWarningMessage("File '$filename' has a '$contenttypeslug' with a missing title field! Skipping record.");

            return false;
        }

        // If not given a publish date, set it to now
        if (!isset($values['datepublish'])) {
            $values['datepublish'] = $status == 'published' ? date('Y-m-d H:i:s') : null;
        }

        // Set up default meta
        $meta = [
            'slug'        => $slug,
            'datecreated' => (isset($values['datecreated'])) ? $values['datecreated'] : date('Y-m-d H:i:s'),
            'ownerid'     => 1,
        ];

        $values = Arr::replaceRecursive($values, $meta);

        // Create and save the content
        /** @var Repository $repo */
        $repo = $this->app['storage']->getRepository($contenttypeslug);
        $record = $repo->create(['contenttype' => $contenttypeslug, 'status' => $status]);

        $record->setValues($values);

        foreach ($repo->getClassMetadata()->getFieldMappings() as $field) {
            if (is_a($field['fieldtype'], RelationType::class, true)) {
                if (count($values[$field['fieldname']])) {
                    $this->relationQueue[$contenttypeslug . '/' . $values['slug']] = array_merge(
                        (array) $this->relationQueue[$contenttypeslug][$values['slug']],
                        $values[$field['fieldname']]
                    );
                }
            }
        }

        if ($repo->save($record) === false) {
            $this->setWarning(true)->setWarningMessage("Failed to imported record with title: {$values['title']} from '$filename'! Skipping record.");

            return false;
        }
        $this->setNotice(true)->setNoticeMessage("Imported record to {$contenttypeslug} with title: {$values['title']}.");

        return true;
    }

    /**
     * Test is a record already exists.
     *
     * @param string $contenttypeslug
     * @param string $slug
     *
     * @return boolean
     */
    protected function isRecordUnique($contenttypeslug, $slug)
    {
        if ($this->allowOverwrite) {
            return true;
        }
        $record = $this->app['storage']->getContent("$contenttypeslug/$slug");
        if (empty($record)) {
            return true;
        }

        return false;
    }

    /**
     *   Since relations can't be processed until we are sure all the individual records are saved this goes
     *   through the queue after an import and links up all the related ones.
     */
    protected function processRelationQueue()
    {
        foreach ($this->relationQueue as $source => $links) {
            $entity = $this->app['query']->getContent($source);
            $relations = [];
            foreach ($links as $linkKey) {
                $relation = $this->app['query']->getContent($linkKey);
                $relations[(string)$relation->getContentType()][] = $relation->getId();
            }
            $related = $this->app['storage']->createCollection(Relations::class);
            $related->setFromPost($relations, $entity);
            $entity->setRelation($related);
            $this->app['storage']->save($entity);
        }
    }
}
