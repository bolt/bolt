<?php

namespace Bolt\Database\Migration;

use Bolt\Application;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Dumper;

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
     * @param string $file
     *
     * @return boolean
     */
    public function exportContenttypesRecords()
    {
        if ($this->getError()) {
            return $this;
        }

        foreach ($this->contenttypes as $contenttype) {
            $this->exportContenttypeRecords($contenttype);
        }
    }

    /**
     * Set the migration files.
     *
     * Also creates an output file object.
     *
     * @see \Bolt\Database\Migration\AbstractMigration::setMigrationFiles()
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
     * @param string $contenttype
     *
     * @return boolean
     */
    private function exportContenttypeRecords($contenttype)
    {
        // Get all the records for the contenttype
        $records = $this->app['storage']->getContent($contenttype);

        $output = array();
        foreach ($records as $record) {
            $values = $record->getValues();
            unset($values['id']);
            $output[$contenttype] = $values;

            $this->writeMigrationFile($output, true);
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
            $this->contenttypes[$contenttypeslugs] = $contenttype;
        }

        return this;
    }

    /**
     * Write a migration file.
     *
     * This function will determine what type based on extension.
     *
     * @param array   $data   The data to write out
     * @param boolean $append Whether to append or abort file writing if a file exists
     *
     * @return array
     */
    protected function writeMigrationFile($data, $append = false)
    {
        $file = $this->files[$this->hash]['file']->getFilename();
//         $type = $this->files[$this->hash]['type'];

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
        return $this->files[$this->hash]['output']->addRecord($data);
//         if ($type === 'yaml') {
//             return $this->writeYamlFile($file, $data);
//         } else {
//             return $this->writeJsonFile($file, $data);
//         }
    }

    /**
     * Write a YAML migration file.
     *
     * @param string  $file   File name
     * @param array   $data   The data to write out
     *
     * @return array
     */
//     private function writeYamlFile($file, $data)
//     {
//         // Get a new YAML dumper
//         $dumper = new Dumper();

//         // Generate the YAML string
//         try {
//             $yaml = $dumper->dump($data, 4);
//         } catch (Exception $e) {
//             $this->setError(true)->setErrorMessage("Unable to generate valid YAML data!");

//             return false;
//         }

//         if (file_put_contents($file, $yaml, FILE_APPEND) === false) {
//             $this->setError(true)->setErrorMessage("Unable to write YAML data to '$file'!");

//             return false;
//         }

//         return true;
//     }

    /**
     * Write a JSON migration file.
     *
     * @param string  $file   File name
     * @param array   $data   The data to write out
     *
     * @return array
     */
//     private function writeJsonFile($file, $data)
//     {
//         // Generate the JSON string
//         $json = json_encode($data);

//         if ($json === false) {
//             $this->setError(true)->setErrorMessage("Unable to generate valid JSON data!");

//             return false;
//         }

//         if (file_put_contents($file, $json, FILE_APPEND) === false) {
//             $this->setError(true)->setErrorMessage("Unable to write JSON data to '$file'!");

//             return false;
//         }

//         return true;
//     }
}
