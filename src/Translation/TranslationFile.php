<?php

namespace Bolt\Translation;

use Silex;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Escaper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Bolt\Translation\Translator as Trans;

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
        $path = '/resources/translations/' . $locale . '/' . $domain . '.' . $locale . '.yml';

        // If long locale dir doesn't exists try short locale and return it if that exists
        if (strlen($locale) == 5 && !is_dir($this->app['paths']['apppath'] . $path)) {
            $paths = $this->buildPath($domain, substr($locale, 0, 2));

            if (is_dir($paths[0])) {
                return $paths;
            }
        }

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
            ->in(__DIR__ . DIRECTORY_SEPARATOR . '..');

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
                    // Skip whitespace and comments between "__" and "("
                    $token = $next();

                    // Found "("?
                    if ($x < $num_tokens && !is_array($token) && $token == '(') {
                        // Skip whitespace and comments between "__()" and first function argument
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
     * @param array $hinting Translation data that can be used as hinting
     * @return string
     */
    private function buildNewContent($newTranslations, $savedTranslations, $hinting = array())
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
            '# Warning: Translations are in the process of being moved to a new keyword-based translation' . "\n" .
            '#          at the moment. This is an ongoing process. Translations currently in the ' . "\n" .
            '#          repository are automatically mapped to the new scheme. Be aware that there ' . "\n" .
            '#          can be a race condition between that process and your PR so that it\'s ' . "\n" .
            '#          eventually necessary to remap your translations. If you\'re planning on ' . "\n" .
            '#          updating your translations, it\'s best to ask on IRC to time your contribution' . "\n" .
            '#          in order to prevent merge conflicts.' . "\n\n";
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
                    $thint = $this->app['translator']->trans($key);
                    if ($thint == $key) {
                        $thint = isset($hinting[$key]) ? $hinting[$key] : '';
                    }
                    $content .= '#' . ($thint ? ' ' . Escaper::escapeWithDoubleQuotes($thint) : '') . "\n";
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
                $savedTranslations = Yaml::parse($this->absPath);

                if ($savedTranslations === null) {
                    return array(); // File seems to be empty
                }

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
                $flattened = array();
                $flatten($savedTranslations);

                return $flattened;
            } catch (ParseException $e) {
                $this->app['session']->getFlashBag()->set('error', '<strong>Unable to parse the YAML translations</strong><br>' . $e->getMessage());
                // Todo: do something better than just returning an empty array
            }
        }

        return array();
    }

    /**
     * Get the content of the info translation file or the fallback file
     *
     * @return string
     */
    private function contentInfo()
    {
        $path = $this->absPath;

        // if the file doesn't exist yet, point to the fallback one
        if (!file_exists($path) || filesize($path) < 10) {
            // fallback
            list($path) = $this->buildPath('infos', 'en_GB');

            if (!file_exists($path)) {
                $this->app['session']->getFlashBag()->set('error', 'Locale infos yml file not found. Fallback also not found.');

                // fallback failed
                return null;
            }
            // we got the fallback, notify user we loaded the fallback
            $this->app['session']->getFlashBag()->set('warning', 'Locale infos yml file not found, loading the default one.');
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

        try {
            return $this->buildNewContent($newTranslations, $savedTranslations);
        } catch (\Symfony\Component\Translation\Exception\InvalidResourceException $e) {
            // last resort fallback, edit the file
            return file_get_contents($this->absPath);
        }
    }

    /**
     * Gets all translatable strings and returns a translationsfile for messages or contenttypes
     *
     * @return string
     */
    private function contentContenttypes()
    {
        $ctypes = $this->app['config']->get('contenttypes');
        $hinting = array();
        $ctnames = array();
        $newTranslations = array();

        $savedTranslations = $this->readSavedTranslations();
        $this->gatherTranslatableStrings();

        // Add names, labels, …
        foreach ($ctypes as $ctname => $ctype) {
            $keyprefix = 'contenttypes.' . strtolower($ctname) . '.';

            // Names & description
            $setkeys = array(
                'name.plural' => 'name',
                'name.singular' => 'singular_name',
                //'description' => 'description',
            );
            foreach ($setkeys as $setkey => $getkey) {
                $key = $keyprefix . $setkey;

                if (isset($savedTranslations[$key]) && $savedTranslations[$key] !== '') {
                    $newTranslations[$key] = $savedTranslations[$key];
                } else {
                    if (isset($ctype[$getkey]) && $ctype[$getkey] !== '') {
                        $hinting[$key] = $ctype[$getkey];
                    }
                    $newTranslations[$key] = '';
                }
                // Remember names for later usage
                if ($setkey == 'name.plural') {
                    $ctnames[$ctname]['%contenttypes%'] = $newTranslations[$key];
                } elseif ($setkey == 'name.singular') {
                    $ctnames[$ctname]['%contenttype%'] = $newTranslations[$key];
                }
            }
        }

        // Generate strings for contenttypes
        foreach (array_keys($this->translatables) as $key) {
            if (substr($key, 0, 21) == 'contenttypes.generic.') {
                foreach ($ctypes as $ctname => $ctype) {
                    $setkey = 'contenttypes.' . $ctname . '.text.' . substr($key, 21);
                    $newTranslations[$setkey] = isset($savedTranslations[$setkey]) ? $savedTranslations[$setkey] : '';
                    if ($newTranslations[$setkey] === '') {
                        $generic = $this->app['translator']->trans($key);
                        if ($generic != $key) {
                            foreach ($ctnames[$ctname] as $placeholder => $replace) {
                                $generic = str_replace($placeholder, $replace, $generic);
                            }
                            $hinting[$setkey] = $generic;
                        }
                    }
                }
            }
        }

        ksort($newTranslations);

        return $this->buildNewContent($newTranslations, $savedTranslations, $hinting);
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

        // No file, directory not writable
        if (!file_exists($this->absPath) && !is_writable(dirname($this->absPath))) {
            $msg = Trans::__(
                "The translations file '%s' can't be created. You will have to use your own editor to make modifications to this file.",
                $msgRepl
            );
            $this->app['session']->getFlashBag()->set('warning', $msg);

        // Have a file, but not writable
        } elseif (file_exists($this->absPath) && !is_writable($this->absPath)) {
            $msg = Trans::__(
                "The file '%s' is not writable. You will have to use your own editor to make modifications to this file.",
                $msgRepl
            );
            $this->app['session']->getFlashBag()->set('warning', $msg);

        // File is not readable: abort
        } elseif (file_exists($this->absPath) && !is_readable($this->absPath)) {
            $msg = Trans::__("The translations file '%s' is not readable.", $msgRepl);
            $this->app->abort(404, $msg);

        // File is writeable
        } else {
            return true;
        }

        return false;
    }
}
