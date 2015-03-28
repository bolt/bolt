<?php

namespace Bolt\Database\Migration\Input;

use Bolt\Database\Migration\Import;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * JSON import file
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class JsonFile implements InputFileInterface
{
    /** @var Import $import */
    private $import;

    /** @var \SplFileInfo $file */
    private $file;

    /** @var \Seld\JsonLint\JsonParser $parser */
    private $parser;

    /**
     * Constructor.
     *
     * @param Import       $import
     * @param \SplFileInfo $file
     */
    public function __construct(Import $import, \SplFileInfo $file)
    {
        $this->import = $import;
        $this->file   = $file;

        // Slower parser than json_decode(), but gives error handling and useful
        // feedback, and we're not in a critical path!
        $this->parser = new JsonParser();
    }

    /**
     * @see \Bolt\Database\Migration\Input\InputFileInterface::readFile()
     */
    public function readFile()
    {
        $filename = (string) $this->file;
        if ($this->file->isReadable()) {
            try {
                $data = $this->parser->parse(file_get_contents($filename));
                $this->import->setData($data);

                return true;
            } catch (ParsingException $e) {
                $this->import
                    ->setError(true)
                    ->setErrorMessage("File '$filename' has invalid JSON!");

                $details = $e->getDetails();
                foreach ($details as $detail) {
                    $this->import
                        ->setErrorMessage($detail);
                }

                return false;
            }
        } else {
            $this->import
                ->setError(true)
                ->setErrorMessage("File '$filename' not readable!");

            return false;
        }
    }
}
