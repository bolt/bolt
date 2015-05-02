<?php

namespace Bolt\Database\Migration\Output;

use Bolt\Database\Migration\Export;

/**
 * Interface for migration export files
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface OutputFileInterface
{
    /**
     * Constructor.
     *
     * @param Export       $export
     * @param \SplFileInfo $file
     */
    public function __construct(Export $export, \SplFileInfo $file);

    public function __destruct();

    /**
     * Add a record
     *
     * @param array   $data An array of values to write out
     * @param boolean $last Flag that indicates last record
     */
    public function addRecord(array $data, $last);
}
