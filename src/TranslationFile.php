<?php

namespace Bolt;

use Silex;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Escaper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Handles translation file dependent tasks
 */
class TranslationFile
{
    /**
     * Injected Application object
     *
     * @var type
     */
    private $app;

    /**
     * Requested Domain
     *
     * @var type
     */
    private $domain;

    /**
     * Requested locale
     */
    private $locale;

    /**
     * Path to the translation file
     *
     * @var type
     */
    private $absPath;

    /**
     * Project relative path to the translation file
     *
     * @var type
     */
    private $relPath;

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
     * @param string $domain Requested resource
     * @param string $locale Requested locale
     */
    public function __construct(Silex\Application $app, $domain, $locale)
    {
        $this->app = $app;
        $this->domain = $domain;
        $this->locale = $locale;

        // Build Path
        list($this->absPath, $this->relPath) = $this->buildPath($domain, $locale);
    }

    /**
     * Get the path to a tranlsation resource
     *
     * @param string $domain Requested resource
     * @param string $locale Requested locale
     * @return array returnsarray(absolute path, relative path)
     */
    private function buildPath($domain, $locale)
    {
        $shortLocale = substr($locale, 0, 2);
        $path = '/resources/translations/' . $shortLocale . '/' . $domain . '.' . $shortLocale . '.yml';

        return array(
            $this->app['paths']['apppath'] . $path,
            'app' . $path,
        );
    }

    /**
     * Get the path to a tranlsation resource
     *
     * @return array returns array(absolute path, relative path)
     */
    public function path()
    {
        return array($this->absPath, $this->relPath);
    }

    /**
     * Adds a string to the internal list of translatable strings
     *
     * @param string $Text
     */
    private function addTranslatable($Text)
    {
        if (strlen($Text) > 1 && !isset($this->translatables[$Text])) {
            $this->translatables[$Text] = '';
        }
    }

    /**
     * Generates a string for each variation of contenttype/contenttypes
     *
     * @param string $txt String with %contenttype%/%contenttypes% placeholders
     * @return array
     */
    private function genContentTypes($txt)
    {
        $stypes = array();

        foreach (array('%contenttype%' => 'singular_name', '%contenttypes%' => 'name') as $placeholder => $name) {
            if (strpos($txt, $placeholder) !== false) {
                foreach ($this->app['config']->get('contenttypes') as $ctype) {
                    $stypes[] = str_replace($placeholder, $ctype[$name], $txt);
                }
            }
        }

        return $stypes;
    }

    /**
     * Scan twig templates for  __('...' and __("..." and add the strings found to the list of translatable strings
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
            "/\b__\(\s*'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'(?U).*\)/s" => array('\\\'' => '\''), // __('single_quoted_string'…
            '/\b__\(\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"(?U).*\)/s' => array('\"' => '"'), // __("double_quoted_string"…
        );

        foreach ($finder as $file) {
            foreach ($twigRegex as $regex => $stripslashes) {
                if (preg_match_all($regex, $file->getContents(), $matches)) {
                    foreach ($matches[1] as $foundString) {
                        $this->addTranslatable(strtr($foundString, $stripslashes));
                    }
                }
            }
        }
    }

    /**
     * Scan php files for  __('...' and __("..." and add the strings found to the list of translatable strings
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
            ->in($this->app['paths']['apppath'])
            ->in(__DIR__);

        foreach ($finder as $file) {
            $tokens = token_get_all($file->getContents());
            $num_tokens = count($tokens);

            // Skip whitespace and comments
            $next = function () use (&$x, $tokens, $num_tokens) {
                $token = $tokens[++$x];
                while ($x < $num_tokens && is_array($token) && ($token[0] == T_WHITESPACE || $token[0] == T_COMMENT)) {
                    $token = $tokens[++$x];
                }

                return $token;
            };
            // Test if token is string, whitespace or comment
            $isArg = function ($token) {
                if (is_array($token)) {
                    if ($token[0] == T_CONSTANT_ENCAPSED_STRING ||
                        $token[0] == T_WHITESPACE ||
                        $token[0] == T_COMMENT
                    ) {
                        return true;
                    }
                } elseif (is_string($token) && $token == '.') {
                    return true;
                }

                return false;
            };

            for ($x = 0; $x < $num_tokens; $x++) {
                $token = $tokens[$x];
                // Found function __()
                if (is_array($token) && $token[0] == T_STRING && $token[1] == '__') {
                    // Skip whitespace and comments between "___" and "("
                    $token = $next();

                    // Found "("?
                    if ($x < $num_tokens && !is_array($token) && $token == '(') {
                        // Skip whitespace and comments between "(___)" and first function argument
                        $token = $next();
                        // Found String?
                        if (is_array($token) && $token[0] == T_CONSTANT_ENCAPSED_STRING) {
                            $string = '';
                            // Get string, also if concatenated
                            while ($x < $num_tokens && $isArg($token)) {
                                if (is_array($token) && $token[0] == T_CONSTANT_ENCAPSED_STRING) {
                                    $raw = substr($token[1], 1, strlen($token[1]) - 2);
                                    if (substr($token[1], 0, 1) == '"') {
                                        // Double quoted string
                                        $string .= str_replace('\"', '"', $raw);
                                    } else {
                                        // Single quoted string
                                        $string .= str_replace('\\\'', '\'', $raw);
                                    }
                                }
                                $token = $tokens[++$x];
                            }
                            $this->addTranslatable($string);
                            //
                            // TODO: retrieve domain?
                        }
                    }
                }
            }
        }
    }

    /**
     *  Add fields names and labels for contenttype (forms) to the list of translatable strings
     */
    private function scanContenttypeFields()
    {
        foreach ($this->app['config']->get('contenttypes') as $contenttype) {
            foreach ($contenttype['fields'] as $fkey => $field) {
                if ($field['label'] !== '') {
                    $this->addTranslatable($field['label']);
                } else {
                    $this->addTranslatable(ucfirst($fkey));
                }
            }
        }
    }

    /**
     *  Add relation names and labels to the list of translatable strings
     */
    private function scanContenttypeRelations()
    {
        foreach ($this->app['config']->get('contenttypes') as $contenttype) {
            if (array_key_exists('relations', $contenttype)) {
                foreach ($contenttype['relations'] as $fkey => $field) {
                    if (isset($field['label']) && $field['label'] !== '') {
                        $this->addTranslatable($field['label']);
                    } else {
                        $this->addTranslatable(ucfirst($fkey));
                    }
                }
            }
        }
    }

    /**
     * Add name ans singular names for taxonomies to the list of translatable strings
     */
    private function scanTaxonomies()
    {
        foreach ($this->app['config']->get('taxonomy') as $value) {
            foreach (array('name', 'singular_name') as $key) {
                $this->addTranslatable($value[$key]);
            }
        }
    }

    /**
     * Find all twig templates and bolt php code, extract translatables strings, merge with existing translations
     *
     * @return array
     */
    private function gatherTranslatableStrings()
    {
        $this->translatables = array();

        $this->scanTwigFiles();
        $this->scanPhpFiles();
        $this->scanContenttypeFields();
        $this->scanContenttypeRelations();
        $this->scanTaxonomies();

        ksort($this->translatables);
    }

    /**
     * Builds the translations file data with added translations
     *
     * @param array $newTranslations New translation data to write
     * @param array $savedTranslations Translation data read from file
     * @return string
     */
    private function buildNewContent($newTranslations, $savedTranslations)
    {
        // Presort
        $unusedTranslations = $savedTranslations;
        $transByType = array(
            'Unused' => array(' unused messages', array()),
            'TodoReal' => array(' untranslated messages', array()),
            'TodoKey' => array(' untranslated keyword based messages', array()),
            'DoneReal' => array(' translations', array()),
            'DoneKey' => array(' keyword based translations', array()),
        );
        foreach ($newTranslations as $key => $translation) {
            $set = array('trans' => $translation);
            if (preg_match('%^([a-z0-9-]+)\.([a-z0-9-]+)\.([a-z0-9-]+)(?:\.([a-z0-9.-]+))?$%', $key, $match)) {
                $type = 'Key';
                $set['key'] = array_slice($match, 1);
            } else {
                $type = 'Real';
            }
            $done = ($translation === '') ? 'Todo' : 'Done';
            $transByType[$done . $type][1][$key] = $set;
            if (isset($unusedTranslations[$key])) {
                unset($unusedTranslations[$key]);
            }
        }
        foreach ($unusedTranslations as $key => $translation) {
            $transByType['Unused'][1][$key] = array('trans' => $translation);
        }

        // Build List
        $indent = '    ';
        $status = '# ' . $this->relPath . ' – generated on ' . date('Y-m-d H:i:s e') . "\n\n" .
            '# Warning: Translations are moved to a new keyword based translation at the moment.' . "\n" .
            '#          This is an ongoing process. Translations in repo are automatically mapped ' . "\n" .
            '#          to the new scheme. Be aware that there can be a race condition between ' . "\n" .
            '#          that process and your PR so that it\'s eventiually neccessry to remap your' . "\n" .
            '#          translations. So perhaps it\'s best to ask on IRC to time your contribution.' . "\n\n";
        $content = '';

        foreach ($transByType as $type => $transData) {
            list($text, $translations) = $transData;
            // Header
            $count = (count($translations) > 0 ? sprintf('%3s', count($translations)) : ' no');
            $status .= '# ' . $count . $text . "\n";
            if (count($translations) > 0) {
                $content .= "\n" . '#--- ' . str_pad(ltrim($count) . $text . ' ', 74, '-') . "\n\n";
            }
            // List
            $lastKey = array();
            $linebreak = ''; // We want an empty line before each 1st level key
            foreach ($translations as $key => $tdata) {
                // Key
                if ($type == 'DoneKey') {
                    for ($level = 0, $end = count($tdata['key']) - 1; $level < $end; $level++) {
                        if ($level >= count($lastKey) - 1 || $lastKey[$level] != $tdata['key'][$level]) {
                            if ($level == 0) {
                                $content .= $linebreak;
                                $linebreak = "\n";
                            }
                            $content .= str_repeat($indent, $level) . $tdata['key'][$level] . ':' . "\n";
                        }
                    }
                    $lastKey = $tdata['key'];
                    $content .= str_repeat($indent, $level) . $tdata['key'][$level] . ': ';
                } else {
                    $content .= Escaper::escapeWithDoubleQuotes($key) . ': ';
                }
                // Value
                if ($tdata['trans'] === '') {
                    $t = $this->app['translator']->trans($key);
                    $content .= '#' . ($t === '' ? '' : ' ' . Escaper::escapeWithDoubleQuotes(print_r($t, 1))) . "\n";
                } else {
                    $content .= Escaper::escapeWithDoubleQuotes($tdata['trans']) . "\n";
                }
            }
        }

        return $status . $content;
    }

    /**
     * Parses translations file ans returns translations
     *
     * @return array Translations found
     */
    private function readSavedTranslations()
    {
        if (is_file($this->absPath) && is_readable($this->absPath)) {
            try {
                $flattened = array();
                $savedTranslations = Yaml::parse($this->absPath);

                $flatten = function ($data, $prefix = '') use (&$flatten, &$flattened) {
                    if ($prefix) {
                        $prefix .= '.';
                    }
                    foreach ($data as $key => $value) {
                        if (is_array($value)) {
                            $flatten($value, $prefix . $key);
                        } else {
                            $flattened[$prefix . $key] = ($value === null) ? '' : $value;
                        }
                    }
                };
                $flatten($savedTranslations);

                return $flattened;
            } catch (ParseException $e) {
                $app['session']->getFlashBag()->set('error', printf('Unable to parse the YAML translations: %s', $e->getMessage()));
                // Todo: do something better than just returning an empty array

                return array();
            }
        }
    }

    /**
     * Get the content of the info translation file or the fallback file
     *
     * @return string
     */
    private function contentInfo()
    {
        $path = $this->absPath;

        // No gathering here: if the file doesn't exist yet, we load a copy from the locale_fallback version (en)
        if (!file_exists($path) || filesize($path) < 10) {
            list($path) = $this->buildPath('infos', 'en');
        }

        return file_get_contents($path);
    }

    /**
     * Gets all translatable strings and returns a translationsfile for messages or contenttypes
     *
     * @return string
     */
    private function contentMessages()
    {
        $savedTranslations = $this->readSavedTranslations();
        $this->gatherTranslatableStrings();

        // Find already translated strings
        $newTranslations = array();
        foreach (array_keys($this->translatables) as $key) {
            $newTranslations[$key] = isset($savedTranslations[$key]) ? $savedTranslations[$key] : '';
        }
        ksort($newTranslations);

        return $this->buildNewContent($newTranslations, $savedTranslations);
    }

    /**
     * Gets all translatable strings and returns a translationsfile for messages or contenttypes
     *
     * @return string
     */
    private function contentContenttypes()
    {
        $savedTranslations = $this->readSavedTranslations();
        $this->gatherTranslatableStrings();

        // Generate strings for contenttypes
        $newTranslations = array();
        foreach (array_keys($this->translatables) as $key) {
            if (strpos($key, '%contenttype%') !== false || strpos($key, '%contenttypes%') !== false) {
                foreach ($this->genContentTypes($key) as $ctypekey) {
                    $newTranslations[$ctypekey] = isset($savedTranslations[$ctypekey]) ? $savedTranslations[$ctypekey] : '';
                }
            }
        }
        ksort($newTranslations);

        return $this->buildNewContent($newTranslations, $savedTranslations);
    }

    /**
     * Gets all translatable strings and returns a translationsfile for messages or contenttypes
     *
     * @return string
     */
    public function content()
    {
        switch ($this->domain) {
            case 'infos':
                return $this->contentInfo();
            case 'messages':
                return $this->contentMessages();
            default:
                return $this->contentContenttypes();
        }
    }

    /**
     * Checks if translations file is allowed to write to
     *
     * @return bool
     */
    public function isWriteAllowed()
    {
        $msgRepl = array('%s' => $this->relPath);

        // No translations yet: info
        if (!file_exists($this->absPath) && !is_writable(dirname($this->absPath))) {
            $msg = __(
                "The translations file '%s' can't be created. You will have to use your own editor to make modifications to this file.",
                $msgRepl
            );
            $this->app['session']->getFlashBag()->set('info', $msg);
        // File is not readable: abort
        } elseif (file_exists($this->absPath) && !is_readable($this->absPath)) {
            $msg = __("The translations file '%s' is not readable.", $msgRepl);
            $this->app->abort(404, $msg);
        // File is not writeable: warning
        } elseif (!is_writable($this->absPath)) {
            $msg = __(
                "The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                $msgRepl
            );
            $this->app['session']->getFlashBag()->set('warning', $msg);
        // File is writeable
        } else {
            return true;
        }

        return false;
    }
}
