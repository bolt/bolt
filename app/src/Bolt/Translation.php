<?php

namespace Bolt;

use Silex;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Escaper;

/**
 * Handles translation dependent tasks
 */
class Translation
{
    /**
     * Injected Application object
     *
     * @var type
     */
    private $app;

    /**
     * List of all translatable Strings found
     *
     * @var array
     */
    private $translatables = array();

    /**
     * Constructor
     *
     * @param Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Adds a string to the internal list of translatable strings
     *
     * @param string $Text
     */
    private function addTranslatable($Text)
    {
        if (!in_array($Text, $this->translatables) && strlen($Text) > 1) {
            $this->translatables[] = $Text;
        }
    }

    /**
     * Return the previously translated string if exists, otherwise return an empty string
     *
     * @param string $key
     * @param array $translated
     * @return string
     */
    private function getTranslated($key, $translated)
    {
        if (($trans = $this->app['translator']->trans($key)) == $key) {
            if (is_array($translated) && array_key_exists($key, $translated) && !empty($translated[$key])) {
                return $translated[$key];
            } else {
                return '';
            }
        } else {
            return $trans;
        }
    }

    /**
     * Generates a string for each variation of contenttype/contenttypes
     *
     * @param string $txt String with %contenttype%/%contenttypes% placeholders
     * @param array $ctypes List of contenttypes
     * @return array
     */
    private function genContentTypes($txt, $ctypes)
    {
        $stypes = array();

        foreach (array('%contenttype%' => 'singular_name', '%contenttypes%' => 'name') as $placeholder => $name) {
            if (strpos($txt, $placeholder) !== false) {
                foreach ($ctypes as $ctype) {
                    $stypes[] = str_replace($placeholder, $ctype[$name], $txt);
                }
            }
        }

        return $stypes;
    }

    /**
     * Scan twig templates for  __('...' and __("..."
     */
    private function scanTwigFiles()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.twig')
            ->notName('*~')
            ->exclude(array('cache', 'config', 'database', 'resources', 'tests'))
            ->in(dirname($this->app['paths']['themepath']))
            ->in($this->app['paths']['apppath']);

        // Regex from: stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
        $twigRegex = array(
            "/\b__\(\s*'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'(?U).*\)/s", // __('single_quoted_string'…
            '/\b__\(\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"(?U).*\)/s', // __("double_quoted_string"…
        );

        foreach ($finder as $file) {
            foreach ($twigRegex as $regex) {
                if (preg_match_all($regex, $file->getContents(), $matches)) {
                    foreach ($matches[1] as $foundString) {
                        $this->addTranslatable($foundString);
                    }
                }
            }
        }
    }

    /**
     * Scan php files for  __('...' and __("..."
     *
     * All translatables strings have to be called with:
     * __("text", $params=array(), $domain='messages', locale=null) // $app['translator']->trans()
     * __("text", count, $params=array(), $domain='messages', locale=null) // $app['translator']->transChoice()
     */
    private function scanPhpFiles()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('*~')
            ->exclude(array('cache', 'config', 'database', 'resources', 'tests'))
            ->in(dirname($this->app['paths']['themepath']))
            ->in($this->app['paths']['apppath']);

        foreach ($finder as $file) {
            $tokens = token_get_all($file->getContents());
            $num_tokens = count($tokens);
            for ($x = 0; $x < $num_tokens; $x++) {
                $token = $tokens[$x];
                if (is_array($token) && $token[0] == T_STRING && $token[1] == '__') {
                    $token = $tokens[++$x];
                    if ($x < $num_tokens && is_array($token) && $token[0] == T_WHITESPACE) {
                        $token = $tokens[++$x];
                    }
                    if ($x < $num_tokens && !is_array($token) && $token == '(') {
                        // In our func args...
                        $token = $tokens[++$x];
                        if ($x < $num_tokens && is_array($token) && $token[0] == T_WHITESPACE) {
                            $token = $tokens[++$x];
                        }
                        if (!is_array($token)) {
                            // Give up
                            continue;
                        }
                        if ($token[0] == T_CONSTANT_ENCAPSED_STRING) {
                            $this->addTranslatable(substr($token[1], 1, strlen($token[1]) - 2));
                            // TODO: retrieve domain?
                        }
                    }
                }
            }
        }
    }

    /**
     *  Add fields name|label for contenttype (forms)
     */
    private function scanContenttypeFields()
    {
        foreach ($this->app['config']->get('contenttypes') as $ckey => $contenttype) {
            foreach ($contenttype['fields'] as $fkey => $field) {
                if (isset($field['label'])) {
                    $this->addTranslatable($field['label']);
                } else {
                    $this->addTranslatable(ucfirst($fkey));
                }
            }
        }
    }

    /**
     *  Add relation name|label if exists
     */
    private function scanContenttypeRelations()
    {
        foreach ($this->app['config']->get('contenttypes') as $ckey => $contenttype) {
            if (array_key_exists('relations', $contenttype)) {
                foreach ($contenttype['relations'] as $fkey => $field) {
                    if (isset($field['label'])) {
                        $this->addTranslatable($field['label']);
                    } else {
                        $this->addTranslatable(ucfirst($fkey));
                    }
                }
            }
        }
    }

    /**
     * Add name + singular_name for taxonomies
     */
    private function scanTaxonomies()
    {
        foreach ($this->app['config']->get('taxonomy') as $txkey => $value) {
            foreach (array('name', 'singular_name') as $key) {
                $this->addTranslatable($value[$key]);
            }
        }
    }

    /**
     * Find all twig templates and bolt php code, extract translatables strings, merge with existing translations
     *
     * @param type $locale
     * @param array $translated
     * @return array
     */
    public function gatherTranslatableStrings($locale = null, $translated = array())
    {
        $ctypes = $this->app['config']->get('contenttypes');

        // Step one: gather all translatable strings

        $this->translatables = array();

        $this->scanTwigFiles();
        $this->scanPhpFiles();
        $this->scanContenttypeFields();
        $this->scanContenttypeRelations();
        $this->scanTaxonomies();

        sort($this->translatables);

        // Step 2: find already translated strings

        if (!$locale) {
            $locale = $this->app['request']->getLocale();
        }
        $msg_domain = array(
            'translated' => array(),
            'not_translated' => array(),
        );
        $ctype_domain = array(
            'translated' => array(),
            'not_translated' => array(),
        );

        foreach ($this->translatables as $idx => $key) {
            $key = stripslashes($key);
            $raw_key = $key;
            $key = Escaper::escapeWithDoubleQuotes($key);
            if (($trans = $this->getTranslated($raw_key, $translated)) == '' &&
                ($trans = $this->getTranslated($key, $translated)) == ''
            ) {
                $msg_domain['not_translated'][] = $key;
            } else {
                $trans = Escaper::escapeWithDoubleQuotes($trans);
                $msg_domain['translated'][$key] = $trans;
            }
            // Step 3: generate additionals strings for contenttypes
            if (strpos($raw_key, '%contenttype%') !== false || strpos($raw_key, '%contenttypes%') !== false) {
                foreach ($this->genContentTypes($raw_key, $ctypes) as $ctypekey) {
                    $key = Escaper::escapeWithDoubleQuotes($ctypekey);
                    if (($trans = $this->getTranslated($ctypekey, $translated)) == '' &&
                        ($trans = $this->getTranslated($key, $translated)) == ''
                    ) {
                        // Not translated
                        $ctype_domain['not_translated'][] = $key;
                    } else {
                        $trans = Escaper::escapeWithDoubleQuotes($trans);
                        $ctype_domain['translated'][$key] = $trans;
                    }
                }
            }
        }

        sort($msg_domain['not_translated']);
        ksort($msg_domain['translated']);

        sort($ctype_domain['not_translated']);
        ksort($ctype_domain['translated']);

        return array($msg_domain, $ctype_domain);
    }
}
