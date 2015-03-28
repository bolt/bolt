<?php

namespace Bolt\Database\Migration\Input;

use Bolt\Database\Migration\Import;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Interface for migration import files
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface InputFileInterface
{
    /**
     * Constructor.
     *
     * @param Import       $import
     * @param \SplFileInfo $file
     */
    public function __construct(Import $import, \SplFileInfo $file);

    /**
     * Read an import file.
     *
     * @return string
     */
    public function readFile();
}
