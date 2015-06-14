<?php
namespace Bolt\Storage\Migration\Input;

use Bolt\Storage\Migration\Import;

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
