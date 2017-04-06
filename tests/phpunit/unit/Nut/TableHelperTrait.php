<?php

namespace Bolt\Tests\Nut;

use Symfony\Component\Console\Tester\CommandTester;

/**
 * Table console output helper trait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait TableHelperTrait
{
    protected function getNormalOuput()
    {
        $tester = $this->getCommandTester();

        $tester->execute([]);

        return $tester->getDisplay();
    }

    protected function getMatchingLineNumber($pattern, $display)
    {
        if (is_string($display)) {
            $display = explode("\n", $display);
        }
        $linesFound = preg_grep($pattern, $display);

        return key($linesFound);
    }

    /**
     * @return CommandTester
     */
    abstract protected function getCommandTester();
}
