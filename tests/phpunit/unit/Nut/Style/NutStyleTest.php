<?php

namespace Bolt\Tests\Nut\Style;

use Bolt\Nut\Style\NutStyle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @covers \Bolt\Nut\Style\NutStyle
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class NutStyleTest extends TestCase
{
    public function testIsQuiet()
    {
        $input = new ArgvInput();
        $output = new BufferedOutput(OutputInterface::VERBOSITY_QUIET);
        $style = new NutStyle($input, $output);

        $this->assertTrue($style->isQuiet());
        $this->assertFalse($style->isVerbose());
        $this->assertFalse($style->isVeryVerbose());
        $this->assertFalse($style->isDebug());
    }

    public function testIsVerbose()
    {
        $input = new ArgvInput();
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
        $style = new NutStyle($input, $output);

        $this->assertFalse($style->isQuiet());
        $this->assertTrue($style->isVerbose());
        $this->assertFalse($style->isVeryVerbose());
        $this->assertFalse($style->isDebug());
    }

    public function testVeryVerbose()
    {
        $input = new ArgvInput();
        $output = new BufferedOutput(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $style = new NutStyle($input, $output);

        $this->assertFalse($style->isQuiet());
        $this->assertTrue($style->isVerbose());
        $this->assertTrue($style->isVeryVerbose());
        $this->assertFalse($style->isDebug());
    }

    public function testIsDebug()
    {
        $input = new ArgvInput();
        $output = new BufferedOutput(OutputInterface::VERBOSITY_DEBUG);
        $style = new NutStyle($input, $output);

        $this->assertFalse($style->isQuiet());
        $this->assertTrue($style->isVerbose());
        $this->assertTrue($style->isVeryVerbose());
        $this->assertTrue($style->isDebug());
    }
}
