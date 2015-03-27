<?php

namespace Bolt\Database\Migration;

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
}
