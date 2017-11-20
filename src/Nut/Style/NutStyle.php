<?php

namespace Bolt\Nut\Style;

use Bolt\Nut\Helper\QuestionHelper;
use Bolt\Nut\Output\NutStyleInterface;
use Bolt\Nut\Output\OverwritableOutput;
use Bolt\Nut\Output\OverwritableOutputInterface;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Nut custom style.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class NutStyle extends SymfonyStyle implements NutStyleInterface
{
    /** @var InputInterface */
    protected $input;
    /** @var OverwritableOutputInterface */
    protected $output;
    /** @var QuestionHelper */
    protected $questionHelper;

    /**
     * {@inheritdoc}
     */
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        if (!$output instanceof OverwritableOutputInterface) {
            $output = new OverwritableOutput($output);
        }
        $this->output = $output;
        parent::__construct($input, $output);
    }

    /**
     * Create from Composer IO.
     *
     * @param IOInterface $io
     *
     * @return NutStyle
     */
    public static function fromComposer(IOInterface $io)
    {
        $input = new ArrayInput([]);
        $input->setInteractive($io->isInteractive());

        if ($io instanceof ConsoleIO) {
            $ref = new \ReflectionProperty($io, 'output');
            $ref->setAccessible(true);
            $output = $ref->getValue($io);
            if ($output instanceof ConsoleOutputInterface) {
                $output = $output->getErrorOutput();
            }
        } else {
            $output = new NullOutput();
        }

        return new static($input, $output);
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive()
    {
        return $this->input->isInteractive();
    }

    /**
     * {@inheritdoc}
     */
    public function askThenRemove($question, $default = null, $validator = null)
    {
        $question = new Question($question, $default);
        $question->setValidator($validator);

        return $this->askQuestionThenRemove($question);
    }

    /**
     * {@inheritdoc}
     */
    public function askHiddenThenRemove($question, $validator = null)
    {
        $question = new Question($question);

        $question->setHidden(true);
        $question->setValidator($validator);

        return $this->askQuestionThenRemove($question);
    }

    /**
     * {@inheritdoc}
     */
    public function confirmThenRemove($question, $default = true)
    {
        return $this->askQuestionThenRemove(new ConfirmationQuestion($question, $default));
    }

    /**
     * {@inheritdoc}
     */
    public function choiceThenRemove($question, array $choices, $default = null)
    {
        if ($default !== null) {
            $values = array_flip($choices);
            $default = $values[$default];
        }

        return $this->askQuestionThenRemove(new ChoiceQuestion($question, $choices, $default));
    }

    /**
     * {@inheritdoc}
     */
    public function askQuestionThenRemove(Question $question)
    {
        if (!$this->questionHelper) {
            $this->questionHelper = new QuestionHelper();
        }

        return $this->questionHelper->askThenRemove($this->input, $this, $question);
    }

    /**
     * {@inheritdoc}
     */
    public function capture()
    {
        $this->output->capture();
    }

    /**
     * {@inheritdoc}
     */
    public function remove()
    {
        $this->output->remove();
    }

    /**
     * {@inheritdoc}
     */
    public function captureUserInput($input)
    {
        $this->output->captureUserInput($input);
    }

    /**
     * {@inheritdoc}
     */
    public function isQuiet()
    {
        return $this->getVerbosity() === self::VERBOSITY_QUIET;
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose()
    {
        return self::VERBOSITY_VERBOSE <= $this->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose()
    {
        return self::VERBOSITY_VERY_VERBOSE <= $this->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return self::VERBOSITY_DEBUG <= $this->getVerbosity();
    }
}
