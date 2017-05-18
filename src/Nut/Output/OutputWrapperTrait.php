<?php

namespace Bolt\Nut\Output;

use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait to help create OutputInterface wrappers.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
trait OutputWrapperTrait
{
    /** @var OutputInterface */
    protected $output;

    public function write($messages, $newline = false, $options = 0)
    {
        $this->output->write($messages, $newline, $options);
    }

    public function writeln($messages, $options = 0)
    {
        $this->output->writeln($messages, $options);
    }

    public function isQuiet()
    {
        return OutputInterface::VERBOSITY_QUIET === $this->getVerbosity();
    }

    public function isVerbose()
    {
        return OutputInterface::VERBOSITY_VERBOSE <= $this->getVerbosity();
    }

    public function isVeryVerbose()
    {
        return OutputInterface::VERBOSITY_VERY_VERBOSE <= $this->getVerbosity();
    }

    public function isDebug()
    {
        return OutputInterface::VERBOSITY_DEBUG <= $this->getVerbosity();
    }

    public function setVerbosity($level)
    {
        $this->output->setVerbosity($level);
    }

    public function getVerbosity()
    {
        return $this->output->getVerbosity();
    }

    public function setDecorated($decorated)
    {
        $this->output->setDecorated($decorated);
    }

    public function isDecorated()
    {
        return $this->output->isDecorated();
    }

    public function setFormatter(OutputFormatterInterface $formatter)
    {
        $this->output->setFormatter($formatter);
    }

    public function getFormatter()
    {
        return $this->output->getFormatter();
    }
}
