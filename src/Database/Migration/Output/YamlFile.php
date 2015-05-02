<?php

namespace Bolt\Database\Migration\Output;

use Bolt\Database\Migration\Export;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Yaml\Dumper;

/**
 * YAML export file
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class YamlFile implements OutputFileInterface
{
    /** @var Export $export */
    private $export;

    /** @var \SplFileInfo $file */
    private $file;

    /** @var \Symfony\Component\Yaml\Dumper */
    private $dumper;

    /**
     * Constructor.
     *
     * @param Export       $export
     * @param \SplFileInfo $file
     */
    public function __construct(Export $export, \SplFileInfo $file)
    {
        $this->export = $export;
        $this->file   = $file;

        // Get a new YAML dumper
        $this->dumper = new Dumper();
    }

    public function __destruct()
    {
    }

    /**
     * @see \Bolt\Database\Migration\File\MigrationFileInterface::addRecord()
     */
    public function addRecord(array $data, $last)
    {
        // Generate the YAML string
        try {
            // Add the record as an indexed array of itself as we're writing @author gawain
            // recorc/row at a time and… YAML.
            $yaml = $this->dumper->dump(array($data), 4);
        } catch (\Exception $e) {
            $this->export
                ->setError(true)
                ->setErrorMessage("Unable to generate valid YAML data!");

            return false;
        }

        $file = (string) $this->file;
        if (file_put_contents($file, $yaml, FILE_APPEND) === false) {
            $this->export
                ->setError(true)
                ->setErrorMessage("Unable to write YAML data to '$file'!");

            return false;
        }

        return true;
    }
}
