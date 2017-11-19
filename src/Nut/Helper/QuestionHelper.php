<?php

namespace Bolt\Nut\Helper;

use Bolt\Nut\Output\OverwritableOutputInterface;
use GuzzleHttp\Psr7\FnStream;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\StreamWrapper;
use Symfony\Component\Console\Helper\SymfonyQuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * A QuestionHelper that can remove all output when done.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class QuestionHelper extends SymfonyQuestionHelper
{
    /** @var bool */
    private $remove;
    /** @var Question */
    private $question;
    /** @var OverwritableOutputInterface */
    private $output;
    /** @var bool */
    private $autocomplete;
    /** @var bool */
    private static $stty;

    /**
     * Constructor.
     */
    public function __construct()
    {
        // Initialize input stream.
        $this->setInputStream(null);
    }

    /**
     * Ask a question to the user and then remove the question & answer.
     *
     * @param InputInterface              $input
     * @param OverwritableOutputInterface $output
     * @param Question                    $question
     *
     * @return mixed|string The user answer
     */
    public function askThenRemove(InputInterface $input, OverwritableOutputInterface $output, Question $question)
    {
        // Following suit of ask().
        if (!$input->isInteractive()) {
            return $question->getDefault();
        }

        $this->remove = true;
        $this->question = $question;
        $this->output = $output;
        $this->autocomplete = $this->question->getAutocompleterValues() !== null && $this->hasSttyAvailable();

        $this->output->capture();

        // Wrap normalizer to remove latest question & answer since
        // it is called for each iteration if answer is not valid.
        $normalizer = $question->getNormalizer();
        $question->setNormalizer(function ($value) use ($normalizer) {
            $this->output->remove();
            $this->output->capture();

            if (is_callable($normalizer)) {
                return $normalizer($value);
            }

            return $value;
        });

        try {
            $value = $this->ask($input, $this->output, $question);
        } finally {
            $this->remove = false;
            $this->question = null;
            $this->output->remove();
            $this->output = null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * Wrap input stream to call our onRead method.
     */
    public function setInputStream($stream)
    {
        $stream = new Stream($stream ?: STDIN);
        $stream = FnStream::decorate($stream, [
            'read' => function ($length) use ($stream) {
                $ret = $stream->read($length);
                $this->onRead($ret);

                return $ret;
            },
        ]);
        $stream = StreamWrapper::getResource($stream);

        parent::setInputStream($stream);
    }

    /**
     * On input stream read.
     *
     * We capture user input here if applicable.
     *
     * We can't use the return value of ask() because it is trimmed.
     * We need to account for those extra characters when overwriting.
     *
     * @param string $data
     */
    protected function onRead($data)
    {
        if (!$this->remove || $this->autocomplete || $this->question->isHidden()) {
            return;
        }

        $this->output->captureUserInput($data);
    }

    /**
     * Returns whether Stty is available or not.
     *
     * @return bool
     */
    private function hasSttyAvailable()
    {
        if (self::$stty !== null) {
            return self::$stty;
        }

        exec('stty 2>&1', $output, $exitcode);

        return self::$stty = $exitcode === 0;
    }
}
