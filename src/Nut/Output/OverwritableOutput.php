<?php

namespace Bolt\Nut\Output;

use Bolt\Nut\Helper\Terminal;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * An Output wrapper that can capture groups of output and remove/overwrite them later.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class OverwritableOutput implements OverwritableOutputInterface
{
    use OutputWrapperTrait;

    /** @var BufferedOutput */
    private $buffer;
    /** @var Capture[] */
    private $captures = [];

    /**
     * Constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->buffer = new BufferedOutput($output->getVerbosity(), $output->isDecorated(), $output->getFormatter());
    }

    /**
     * {@inheritdoc}
     */
    public function capture()
    {
        array_unshift($this->captures, new Capture());
    }

    /**
     * {@inheritdoc}
     */
    public function captureUserInput($input)
    {
        if (!$this->captures) {
            $this->capture();
        }
        $this->captures[0]->append($input);
    }

    /**
     * {@inheritdoc}
     */
    public function remove()
    {
        if (!$this->captures) {
            return;
        }

        $capture = array_shift($this->captures);

        if (!$this->isDecorated()) {
            return;
        }

        $overwrite = $capture->overwrite($this->getFormatter(), Terminal::getWidth());

        $this->output->write($overwrite);
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $options = 0)
    {
        if ($this->captures) {
            $this->buffer->write($messages, $newline, $options);
            $buffer = $this->buffer->fetch();
            $this->captures[0]->append($buffer);
        }

        $this->output->write($messages, $newline, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, $options = 0)
    {
        $this->write($messages, true, $options);
    }
}
