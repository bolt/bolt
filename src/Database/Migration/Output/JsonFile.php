<?php

namespace Bolt\Database\Migration\Output;

use Bolt\Database\Migration\Export;
use Symfony\Component\HttpFoundation\File\File;

/**
 * JSON export file
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class JsonFile implements OutputFileInterface
{
    /** @var Export $export */
    private $export;

    /** @var \SplFileInfo $file */
    private $file;

    /** @var boolean $open */
    private $open = false;

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

        /** @deprecated Remove when we drop PHP 5.3 */
        if (!defined('JSON_PRETTY_PRINT')) {
            define('JSON_PRETTY_PRINT', 128);
        }
    }

    public function __destruct()
    {
        $this->closeJsonArray();
    }

    /**
     * @see \Bolt\Database\Migration\File\MigrationFileInterface::addRecord()
     */
    public function addRecord(array $data, $last)
    {
        if (!$this->open) {
            $this->openJsonArray();
        }

        // Generate the JSON string
        $json = json_encode($data, JSON_PRETTY_PRINT);

        if ($json === false) {
            $this->export
                ->setError(true)
                ->setErrorMessage("Unable to generate valid JSON data!");

            return false;
        } elseif (!$last) {
            $json .= ",\n";
        }

        $file = (string) $this->file;
        if (file_put_contents($file, $json, FILE_APPEND) === false) {
            $this->export
                ->setError(true)
                ->setErrorMessage("Unable to write JSON data to '$file'!");

            return false;
        }

        return true;
    }

    /**
     * Open an JSON export file array
     */
    private function openJsonArray()
    {
        file_put_contents((string) $this->file, "[\n", FILE_APPEND);
        $this->open = true;
    }

    /**
     * Close an JSON export file array
     */
    private function closeJsonArray()
    {
        file_put_contents((string) $this->file, "\n]", FILE_APPEND);
    }
}
