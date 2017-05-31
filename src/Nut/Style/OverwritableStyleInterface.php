<?php

namespace Bolt\Nut\Style;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * An extension of StyleInterface that adds user input methods that remove their output afterwards.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
interface OverwritableStyleInterface extends StyleInterface
{
    /**
     * Returns whether the input is interactive.
     *
     * @return bool
     */
    public function isInteractive();

    /**
     * Asks a question then removes the question & answer.
     *
     * @param string        $question
     * @param string|null   $default
     * @param callable|null $validator
     *
     * @return string
     */
    public function askThenRemove($question, $default = null, $validator = null);

    /**
     * Asks a question with the user input hidden then removes the question.
     *
     * @param string        $question
     * @param callable|null $validator
     *
     * @return string
     */
    public function askHiddenThenRemove($question, $validator = null);

    /**
     * Asks for confirmation then removes the question & answer.
     *
     * @param string $question
     * @param bool   $default
     *
     * @return bool
     */
    public function confirmThenRemove($question, $default = true);

    /**
     * Asks a choice question then removes the question & answer.
     *
     * @param string          $question
     * @param array           $choices
     * @param string|int|null $default
     *
     * @return string
     */
    public function choiceThenRemove($question, array $choices, $default = null);

    /**
     * Asks a question then removes the question & answer.
     *
     * @param Question $question
     *
     * @return mixed|string
     */
    public function askQuestionThenRemove(Question $question);
}
