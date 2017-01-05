<?php

namespace Bolt\Twig\Runtime;

use Bolt\Config;
use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Stack;
use Bolt\Translation\Translator as Trans;
use Silex;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Bolt specific Twig functions and filters for backend
 *
 * @internal
 */
class AdminRuntime
{
    /** @var Config */
    private $config;
    /** @var Stack */
    private $stack;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    /** @var \Silex\Application */
    private $app;

    /** @var integer Holds the last used Bolt unique ID. */
    private $buid = 0;

    /**
     * Constructor.
     *
     * @param Config                $config
     * @param Stack                 $stack
     * @param UrlGeneratorInterface $urlGenerator
     * @param Silex\Application     $app
     */
    public function __construct(
        Config $config,
        Stack $stack,
        UrlGeneratorInterface $urlGenerator,
        Silex\Application $app
    ) {
        $this->config = $config;
        $this->stack = $stack;
        $this->urlGenerator = $urlGenerator;
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
                $part[$key] = [];
            }

            $part = & $part[$key];
        }

        $part = $value;
        $this->app['jsdata'] = $jsdata;
    }

    /**
     * Return whether or not a file is stackable.
     *
     * @param FileInterface|string $filename
     *
     * @return boolean
     */
    public function testStackable($filename)
    {
        $stacked = $this->stack->isStackable($filename);

        return $stacked;
    }

    /**
     * Return an array with the items on the stack.
     *
     * @param array|string $types
     *
     * @return array An array of items
     */
    public function stack($types = [])
    {
        if (is_string($types)) {
            $types = array_filter(array_map('trim', explode(',', $types)));
        }

        $files = $this->stack->getList($types);

        return $files;
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
     * @internal param array $args
     * @internal param int   $numArgs
     *
     * @return string Translated content
     */
    public function trans()
    {
        $args = func_get_args();
        $numArgs = func_num_args();

        switch ($numArgs) {
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
        $quotes = [
            'Complexity is your enemy. Any fool can make something complicated. It is hard to make something simple.#Richard Branson',
            'It takes a lot of effort to make things look effortless.#Mark Pilgrim',
            'Perfection is achieved, not when there is nothing more to add, but when there is nothing left to take away.#Antoine de Saint-Exupéry',
            'Everything should be made as simple as possible, but not simpler.#Albert Einstein',
            'Three Rules of Work: Out of clutter find simplicity; From discord find harmony; In the middle of difficulty lies opportunity.#Albert Einstein',
            'There is no greatness where there is not simplicity, goodness, and truth.#Leo Tolstoy',
            'Think simple as my old master used to say - meaning reduce the whole of its parts into the simplest terms, getting back to first principles.#Frank Lloyd Wright',
            'Simplicity is indeed often the sign of truth and a criterion of beauty.#Mahlon Hoagland',
            'Simplicity and repose are the qualities that measure the true value of any work of art.#Frank Lloyd Wright',
            'Nothing is true, but that which is simple.#Johann Wolfgang von Goethe',
            'There is a simplicity that exists on the far side of complexity, and there is a communication of sentiment and attitude not to be discovered by careful exegesis of a text.#Patrick Buchanan',
            'The simplest things are often the truest.#Richard Bach',
            "If you can't explain it to a six year old, you don't understand it yourself.#Albert Einstein",
            'One day I will find the right words, and they will be simple.#Jack Kerouac',
            'Simplicity is the ultimate sophistication.#Leonardo da Vinci',
            'Our life is frittered away by detail. Simplify, simplify.#Henry David Thoreau',
            'The simplest explanation is always the most likely.#Agatha Christie',
            'Truth is ever to be found in the simplicity, and not in the multiplicity and confusion of things.#Isaac Newton',
            'Simplicity is a great virtue but it requires hard work to achieve it and education to appreciate it. And to make matters worse: complexity sells better.#Edsger Wybe Dijkstra',
            "Focus and simplicity. Simple can be harder than complex: You have to work hard to get your thinking clean to make it simple. But it's worth it in the end because once you get there, you can move mountains.#Steve Jobs",
            'The ability to simplify means to eliminate the unnecessary so that the necessary may speak.#Hans Hofmann',
            "I've learned to keep things simple. Look at your choices, pick the best one, then go to work with all your heart.#Pat Riley",
            'A little simplification would be the first step toward rational living, I think.#Eleanor Roosevelt',
            "Making the simple complicated is commonplace; making the complicated simple, awesomely simple, that's creativity.#Charles Mingus",
            'Keep it simple, stupid.#Kelly Johnson',
            "There's a big difference between making a simple product and making a product simple.#Des Traynor",
        ];

        $randomquote = explode('#', $quotes[array_rand($quotes, 1)]);

        $quote = sprintf("“%s”\n<cite>— %s</cite>", $randomquote[0], $randomquote[1]);

        return $quote;
    }

    /**
     * Create a link to edit a .yml file, if a filename is detected in the string. Mostly
     * for use in Flashbag messages, to allow easy editing.
     *
     * @param string $str
     *
     * @return string Resulting string
     */
    public function ymllink($str)
    {
        $matches = [];
        if (preg_match('/ ([a-z0-9_-]+\.yml)/i', $str, $matches)) {
            $path = $this->urlGenerator->generate('fileedit', ['namespace' => 'config', 'file' => $matches[1]]);
            $link = sprintf(' <a href="%s">%s</a>', $path, $matches[1]);
            $str = preg_replace('/ ([a-z0-9_-]+\.yml)/i', $link, $str);
        }

        return $str;
    }

    /**
     * Prepares attributes ready to attach to an html tag.
     *
     * - Handles boolean attributes.
     * - Omits empty attributes if not forced by appending '!' to the name.
     * - JSON encodes array values
     * - Prettied output of class attribute and array data is handled.
     *
     * @param array $attributes
     *
     * @return string Attributes
     */
    public function hattr($attributes)
    {
        // http://www.w3.org/html/wg/drafts/html/master/infrastructure.html#boolean-attributes
        // We implement only a subset for now that is used in Bolt.
        $booleans = ['checked', 'disabled', 'multiple', 'readonly', 'required', 'selected'];
        $return = '';

        $add = function ($name, $value = null) use (&$return) {
            $return .= ' ' . $name . ($value === null ? '' : '="' . htmlspecialchars($value) . '"');
        };

        foreach ($attributes as $name => $value) {
            // Force outputting of empty non booleans.
            $force = substr($name, -1) === '!';
            $name = rtrim($name, '!');

            // Check for being a boolean attribute.
            $isBoolean = in_array($name, $booleans);

            // Assume integer 0, float 0.0 and string "0" as not empty on non booleans.
            $set = !empty($value) || !$isBoolean && (string) $value === '0';

            if ($set || !$isBoolean && $force) {
                if ($isBoolean) {
                    $add($name);
                } elseif ($name === 'name+id') {
                    $add('name', $value);
                    $add('id', $value);
                } elseif ($name === 'class') {
                    $add($name, $this->hclass($value, true));
                } elseif (is_array($value)) {
                    $add($name, json_encode($value));
                } else {
                    $add($name, $value);
                }
            }
        }

        return $return;
    }

    /**
     * Generates pretty class attributes.
     *
     * @param array|string $classes
     * @param boolean      $raw
     *
     * @return string Class attribute
     */
    public function hclass($classes, $raw = false)
    {
        if (is_array($classes)) {
            $classes = join(' ', $classes);
        }
        $classes = preg_split('/ +/', trim($classes));
        $classes = join(' ', $classes);

        if ($raw) {
            return $classes;
        } else {
            return $classes ? ' class="' . htmlspecialchars($classes) . '"' : '';
        }
    }

    /**
     * Returns a unique ID.
     *
     * @return string UID
     */
    public function buid()
    {
        return 'buid-' . ++$this->buid;
    }
}
