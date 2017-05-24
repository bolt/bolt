<?php

namespace Bolt\Nut\Output;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * An Output that can capture groups of output text and remove/overwrite them later.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface OverwritableOutputInterface extends OutputInterface
{
    /**
     * Start capturing output to remove.
     *
     * This should start a new capture each time it is called.
     */
    public function capture();

    /**
     * Remove output from latest capture.
     */
    public function remove();

    /**
     * Add user input to latest capture.
     *
     * This is a special case where the user writes to console but it doesn't go through our output.
     * We need to manually add it to keep the console in sync.
     *
     * @param string $input
     */
    public function captureUserInput($input);
}
