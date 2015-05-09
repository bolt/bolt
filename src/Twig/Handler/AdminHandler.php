<?php

namespace Bolt\Twig\Handler;

use Bolt\Library as Lib;
use Bolt\Translation\Translator as Trans;
use Silex;

/**
 * Bolt specific Twig functions and filters for backend
 *
 * @internal
 */
class AdminHandler
{
    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Add JavaScript data to app['jsdata'].
     *
     * @param string $path
     * @param mixed  $value
     */
    public function addData($path, $value)
    {
        $path = explode('.', $path);

        if (empty($path[0])) {
            return;
        }

        $jsdata = $this->app['jsdata'];
        $part = & $jsdata;

        foreach ($path as $key) {
            if (!isset($part[$key])) {
                $part[$key] = array();
            }

            $part = & $part[$key];
        }

        $part = $value;
        $this->app['jsdata'] = $jsdata;
    }

    public function isChangelogEnabled()
    {
        return $this->app['config']->get('general/changelog/enabled');
    }

    /**
     * Return whether or not an item is on the stack, and is stackable in the first place.
     *
     * @param string $filename File name
     *
     * @return boolean
     */
    public function stacked($filename)
    {
        $stacked = ($this->app['stack']->isOnStack($filename) || !$this->app['stack']->isStackable($filename));

        return $stacked;
    }

    /**
     * Return an array with the items on the stack.
     *
     * @param integer $amount
     * @param string  $type
     *
     * @return array An array of items
     */
    public function stackItems($amount = 20, $type = '')
    {
        $items = $this->app['stack']->listitems($amount, $type);

        return $items;
    }

    /**
     * Renders a particular widget type on the given location.
     *
     * @param string $type     Widget type (e.g. 'dashboard')
     * @param string $location CSS location (e.g. 'right_first')
     *
     * @return null
     */
    public function widget($type = '', $location = '')
    {
        $this->app['extensions']->renderWidgetHolder($type, $location);

        return null;
    }

    /**
     * Convert a Monolog log level to textual equivalent.
     *
     * @param integer $level
     *
     * @return string
     */
    public function logLevel($level)
    {
        if (!is_numeric($level)) {
            return $level;
        }

        try {
            return ucfirst(strtolower(\Monolog\Logger::getLevelName($level)));
        } catch (\Exception $e) {
            return $level;
        }
    }

    /**
     * Translate using our __().
     *
     * @internal
     *
     * @param array   $args
     * @param integer $numArgs
     *
     * @return string Translated content
     */
    public function trans(array $args, $numArgs)
    {
        switch ($numArgs) {
            case 5:
                return Trans::__($args[0], $args[1], $args[2], $args[3], $args[4]);
            case 4:
                return Trans::__($args[0], $args[1], $args[2], $args[3]);
            case 3:
                return Trans::__($args[0], $args[1], $args[2]);
            case 2:
                return Trans::__($args[0], $args[1]);
            case 1:
                return Trans::__($args[0]);
        }

        return null;
    }

    /**
     * Returns a random quote. Just for fun.
     *
     * @return string
     */
    public function randomQuote()
    {
        $quotes = array(
            "Complexity is your enemy. Any fool can make something complicated. It is hard to make something simple.#Richard Branson",
            "It takes a lot of effort to make things look effortless.#Mark Pilgrim",
            "Perfection is achieved, not when there is nothing more to add, but when there is nothing left to take away.#Antoine de Saint-Exupéry",
            "Everything should be made as simple as possible, but not simpler.#Albert Einstein",
            "Three Rules of Work: Out of clutter find simplicity; From discord find harmony; In the middle of difficulty lies opportunity.#Albert Einstein",
            "There is no greatness where there is not simplicity, goodness, and truth.#Leo Tolstoy",
            "Think simple as my old master used to say - meaning reduce the whole of its parts into the simplest terms, getting back to first principles.#Frank Lloyd Wright",
            "Simplicity is indeed often the sign of truth and a criterion of beauty.#Mahlon Hoagland",
            "Simplicity and repose are the qualities that measure the true value of any work of art.#Frank Lloyd Wright",
            "Nothing is true, but that which is simple.#Johann Wolfgang von Goethe",
            "There is a simplicity that exists on the far side of complexity, and there is a communication of sentiment and attitude not to be discovered by careful exegesis of a text.#Patrick Buchanan",
            "The simplest things are often the truest.#Richard Bach",
            "If you can't explain it to a six year old, you don't understand it yourself.#Albert Einstein",
            "One day I will find the right words, and they will be simple.#Jack Kerouac",
            "Simplicity is the ultimate sophistication.#Leonardo da Vinci",
            "Our life is frittered away by detail. Simplify, simplify.#Henry David Thoreau",
            "The simplest explanation is always the most likely.#Agatha Christie",
            "Truth is ever to be found in the simplicity, and not in the multiplicity and confusion of things.#Isaac Newton",
            "Simplicity is a great virtue but it requires hard work to achieve it and education to appreciate it. And to make matters worse: complexity sells better.#Edsger Wybe Dijkstra",
            "Focus and simplicity. Simple can be harder than complex: You have to work hard to get your thinking clean to make it simple. But it's worth it in the end because once you get there, you can move mountains.#Steve Jobs",
            "The ability to simplify means to eliminate the unnecessary so that the necessary may speak.#Hans Hofmann",
            "I've learned to keep things simple. Look at your choices, pick the best one, then go to work with all your heart.#Pat Riley",
            "A little simplification would be the first step toward rational living, I think.#Eleanor Roosevelt",
            "Making the simple complicated is commonplace; making the complicated simple, awesomely simple, that's creativity.#Charles Mingus",
            "Keep it simple, stupid.#Kelly Johnson",
            "There's a big difference between making a simple product and making a product simple.#Des Traynor"
        );

        $randomquote = explode('#', $quotes[array_rand($quotes, 1)]);

        $quote = sprintf("“%s”\n<cite>— %s</cite>", $randomquote[0], $randomquote[1]);

        return $quote;
    }

    /**
     * Create a link to edit a .yml file, if a filename is detected in the string. Mostly
     * for use in Flashbag messages, to allow easy editing.
     *
     * @param string  $str
     * @param boolean $safe
     *
     * @return string Resulting string
     */
    public function ymllink($str, $safe)
    {
        // There is absolutely no way anyone could possibly need this in a "safe" context
        if ($safe) {
            return null;
        }

        $matches = array();
        if (preg_match('/ ([a-z0-9_-]+\.yml)/i', $str, $matches)) {
            $path = Lib::path('fileedit', array('namespace' => 'config', 'file' => $matches[1]));
            $link = sprintf(' <a href="%s">%s</a>', $path, $matches[1]);
            $str = preg_replace('/ ([a-z0-9_-]+\.yml)/i', $link, $str);
        }

        return $str;
    }
}
