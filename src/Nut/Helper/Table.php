<?php

namespace Bolt\Nut\Helper;

use Bolt\Nut\Output\OverwritableOutputInterface;
use Symfony\Component\Console\Helper\Table as BaseTable;

/**
 * Extends Symfony's Table to provide an overwrite method.
 *
 * Table values can be updated then, instead of calling render(),
 * call overwrite() and the previous table will be removed and
 * the new one will be rendered.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class Table extends BaseTable
{
    /** @var OverwritableOutputInterface */
    private $output;
    /** @var bool */
    private $previouslyRendered;

    /**
     * Constructor.
     *
     * @param OverwritableOutputInterface $output
     */
    public function __construct(OverwritableOutputInterface $output)
    {
        $this->output = $output;
        parent::__construct($this->output);
    }

    /**
     * Remove previously rendered table, then render again with new data.
     *
     * This assumes the table is the last output on the terminal.
     */
    public function overwrite()
    {
        if (!$this->previouslyRendered) {
            $this->previouslyRendered = true;
        } else {
            $this->output->remove();
        }
        $this->output->capture();
        $this->render();
    }
}
