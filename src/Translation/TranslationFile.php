<?php

namespace Bolt\Translation;

use Bolt\Translation\Translator as Trans;
use Silex\Application;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Yaml\Escaper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Handles translation file dependent tasks.
 */
class TranslationFile
{
    /** @var \Silex\Application */
    private $app;
    /** @var string Requested Domain. */
    private $domain;
    /** @var string Path to the translation file. */
    private $absPath;
    /** @var string Project relative path to the translation file. */
    private $relPath;
    /** @var array List of all translatable Strings found. */
    private $translatables = [];

    /**
     * Constructor.
     *
     * @param Application $app
     * @param string      $domain Requested resource
     * @param string      $locale Requested locale
     */
    public function __construct(Application $app, $domain, $locale)
    {
        $this->app = $app;
        $this->domain = $domain;

        // Build Path
        list($this->absPath, $this->relPath) = $this->buildPath($domain, $locale);
    }

    /**
     * Get the path to a tranlsation resource.
     *
     * @param string $domain Requested resource
     * @param string $locale Requested locale
     *
     * @return array [absolute path, relative path]
     */
    private function buildPath($domain, $locale)
    {
        $path = '/resources/translations/' . $locale . '/' . $domain . '.' . $locale . '.yml';

        // If long locale dir doesn't exists try short locale and return it if that exists
        if (strlen($locale) == 5 && !is_dir($this->app['path_resolver']->resolve('%root%/app' . $path))) {
            $paths = $this->buildPath($domain, substr($locale, 0, 2));

            if (is_dir($paths[0])) {
                return $paths;
            }
        }

        return [
            $this->app['path_resolver']->resolve('%root%/app' . $path),
            'app' . $path,
        ];
    }

    /**
     * Get the path to a translation resource.
     *
     * @return array [absolute path, relative path]
     */
    public function path()
    {
        return [$this->absPath, $this->relPath];
    }

    /**
     * Adds a string to the internal list of translatable strings.
     *
     * @param string $text
     */
    private function addTranslatable($text)
    {
        if (strlen($text) > 1 && !isset($this->translatables[$text])) {
            $this->translatables[$text] = '';
        }
    }

    /**
     * Scan twig templates for  __('...' and __("..." and add the strings found to the list of translatable strings.
     */
    private function scanTwigFiles()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.twig')
            ->notName('*~')
            ->exclude(['cache', 'config', 'database', 'resources', 'tests', 'bower_components', 'node_modules'])
            ->in($this->app['path_resolver']->resolve('themes'))
            ->in($this->app['path_resolver']->resolve('app')); // yes, app = bad. Will be refactored in 3.4

        // Regex from: stackoverflow.com/questions/5695240/php-regex-to-ignore-escaped-quotes-within-quotes
        $twigRegex = [
            "/\b__\(\s*'([^'\\\\]*(?:\\\\.[^'\\\\]*)*)'(?U).*\)/s" => ['\\\'' => '\''], // __('single_quoted_string'…
            '/\b__\(\s*"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"(?U).*\)/s' => ['\"'   => '"'], // __("double_quoted_string"…
        ];

        foreach ($finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
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
     * Scan php files for  __('...' and __("..." and add the strings found to the list of translatable strings.
     *
     * All translatable strings have to be called with:
     * __("text", params=[], domain='messages', locale=null) // $app['translator']->trans()
     * __("text", count, params=[], domain='messages', locale=null) // $app['translator']->transChoice()
     */
    private function scanPhpFiles()
    {
        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('*~')
            ->exclude(['cache', 'config', 'database', 'resources', 'tests', 'vendor'])
            ->in(__DIR__ . DIRECTORY_SEPARATOR . '..');

        foreach ($finder as $file) {
            /** @var \Symfony\Component\Finder\SplFileInfo $file */
            $tokens = token_get_all($file->getContents());
            $numTokens = count($tokens);

            // Skip whitespace and comments
            $next = function () use (&$x, $tokens, $numTokens) {
                $token = $tokens[++$x];
                while ($x < $numTokens && is_array($token) && ($token[0] == T_WHITESPACE || $token[0] == T_COMMENT)) {
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

            for ($x = 0; $x < $numTokens; ++$x) {
                $token = $tokens[$x];
                // Found function __()
                if (is_array($token) && $token[0] == T_STRING && $token[1] == '__') {
                    // Skip whitespace and comments between "__" and "("
                    $token = $next();

                    // Found "("?
                    if ($x < $numTokens && !is_array($token) && $token == '(') {
                        // Skip whitespace and comments between "__()" and first function argument
                        $token = $next();
                        // Found String?
                        if (is_array($token) && $token[0] == T_CONSTANT_ENCAPSED_STRING) {
                            $string = '';
                            // Get string, also if concatenated
                            while ($x < $numTokens && $isArg($token)) {
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
                        }
                    }
                }
            }
        }
    }

    /**
     * Find all twig templates and bolt php code, extract translatables strings, merge with existing translations.
     */
    private function gatherTranslatableStrings()
    {
        $this->translatables = [];

        $this->scanTwigFiles();
        $this->scanPhpFiles();

        ksort($this->translatables);
    }

    /**
     * Builds the translations file data with added translations.
     *
     * @param array $newTranslations   New translation data to write
     * @param array $savedTranslations Translation data read from file
     * @param array $hinting           Translation data that can be used as hinting
     *
     * @return string
     */
    private function buildNewContent($newTranslations, $savedTranslations, $hinting = [])
    {
        // Presort
        $unusedTranslations = $savedTranslations;
        $transByType = [
            'Unused'   => [' Unused messages', []],
            'TodoReal' => [' Legacy untranslated messages', []],
            'TodoKey'  => [' Untranslated messages', []],
            'DoneReal' => [' Legacy translation messages', []],
            'DoneKey'  => [' Translation messages', []],
        ];
        foreach ($newTranslations as $key => $translation) {
            $set = ['trans' => $translation];
            if (preg_match('%^[a-z0-9-]+\.[a-z0-9-.]+$%', $key)) {
                $type = 'Key';
                $set['key'] = preg_split('%\.%', $key);
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
            $transByType['Unused'][1][$key] = ['trans' => $translation];
        }

        // Build List
        $indent = '    ';
        $status = '# ' . $this->relPath . "\n\n" .
            '# Warning: Translations are in the process of being moved to a new keyword' . "\n" .
            '#          based translation messages. This is an ongoing process. Translations' . "\n" .
            '#          currently in the repository are automatically mapped to the new' . "\n" .
            '#          scheme. Be aware that there can be a race condition between that' . "\n" .
            '#          process and your PR so that it will eventually be necessary to' . "\n" .
            '#          re-map your translations.' . "\n\n";
        $content = '';

        // Set this to true to get nested output.
        $nested = false;

        foreach ($transByType as $type => $transData) {
            list($text, $translations) = $transData;
            // Header
            $count = (count($translations) > 0 ? sprintf('%3s', count($translations)) : '  0');
            $status .= '# ' . $count . $text . "\n";
            if (count($translations) > 0) {
                $content .= "\n" . '#--- ' . str_pad(ltrim($text) . ' ', 74, '-') . "\n\n";
            }
            // List
            $lastKey = [];
            $linebreak = ''; // We want an empty line before each 1st level key
            foreach ($translations as $key => $tdata) {
                // Key
                if ($type === 'DoneKey' || $type == 'TodoKey') {
                    if ($nested) {
                        $differs = false;
                        for ($level = 0, $end = count($tdata['key']) - 1; $level < $end; ++$level) {
                            if ($differs || $level >= count($lastKey) - 1 || $lastKey[$level] != $tdata['key'][$level]) {
                                $differs = true;
                                if ($level === 0) {
                                    $content .= $linebreak;
                                    $linebreak = "\n";
                                }
                                $content .= str_repeat($indent, $level) . $tdata['key'][$level] . ':' . "\n";
                            }
                        }
                        $lastKey = $tdata['key'];
                        $content .= str_repeat($indent, $level) . $tdata['key'][$level] . ': ';
                    } else {
                        $key2 = $tdata['key'][0] . (isset($tdata['key'][1]) ? '.' . $tdata['key'][1] : '');
                        if ($key2 !== $lastKey) {
                            $content .= $linebreak;
                            $linebreak = "\n";
                            $lastKey = $key2;
                        }
                        $content .= $key . ': ';
                    }
                } else {
                    $content .= Escaper::escapeWithDoubleQuotes($key) . ': ';
                }
                // Value
                if ($tdata['trans'] === '') {
                    $thint = Trans::__($key);
                    if ($thint === $key) {
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
     * Parses translations file and returns translations.
     *
     * @return array|null Translations found
     */
    private function readSavedTranslations()
    {
        if (!is_file($this->absPath) || !is_readable($this->absPath)) {
            return null;
        }

        try {
            $savedTranslations = Yaml::parse(file_get_contents($this->absPath), true);
        } catch (ParseException $e) {
            $this->app['logger.flash']->danger('Unable to parse the YAML translations' . $e->getMessage());

            return null;
        }

        if ($savedTranslations === null) {
            return []; // File seems to be empty
        } elseif (!is_array($savedTranslations)) {
            $savedTranslations = [$savedTranslations]; // account for file with one lin
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
        $flattened = [];
        $flatten($savedTranslations);

        return $flattened;
    }

    /**
     * Get the content of the info translation file or the fallback file.
     *
     * @return string
     */
    private function contentInfo()
    {
        $path = $this->absPath;

        // if the file doesn't exist yet, point to the fallback one
        if (!file_exists($path) || filesize($path) < 10) {
            // fallback
            $localeFallbacks = $this->app['locale_fallbacks'];
            list($path) = $this->buildPath('infos', reset($localeFallbacks));

            if (!file_exists($path)) {
                $this->app['logger.flash']->danger('Locale infos yml file not found. Fallback also not found.');

                // fallback failed
                return null;
            }
            // we got the fallback, notify user we loaded the fallback
            $this->app['logger.flash']->warning('Locale infos yml file not found, loading the default one.');
        }

        return file_get_contents($path);
    }

    /**
     * Gets all translatable strings and returns a translationsfile for messages or contenttypes.
     *
     * @return string
     */
    private function contentMessages()
    {
        $savedTranslations = $this->readSavedTranslations();
        // An exception occurred when reading the file
        if ($savedTranslations === null) {
            return '';
        }

        $this->gatherTranslatableStrings();

        // Find already translated strings
        $newTranslations = [];
        foreach (array_keys($this->translatables) as $key) {
            $newTranslations[$key] = isset($savedTranslations[$key]) ? $savedTranslations[$key] : '';
        }
        ksort($newTranslations);

        try {
            return $this->buildNewContent($newTranslations, $savedTranslations);
        } catch (InvalidResourceException $e) {
            // last resort fallback, edit the file
            return file_get_contents($this->absPath);
        }
    }

    /**
     * Gets all translatable strings and returns a translations file for
     * messages.
     *
     * @return string
     */
    public function content()
    {
        if ($this->domain === 'infos') {
            return $this->contentInfo();
        }

        return $this->contentMessages();
    }

    /**
     * Checks if translations file is allowed to write to.
     *
     * @return bool
     */
    public function isWriteAllowed()
    {
        $msgRepl = ['%s' => $this->relPath];

        // No file, directory not writable
        if (!file_exists($this->absPath) && (!is_writable(dirname($this->absPath)) && !is_writable(dirname(dirname($this->absPath))))) {
            $msg = Trans::__(
                "The translations file '%s' can't be created. You will have to use your own editor to make modifications to this file.",
                $msgRepl
            );
            $this->app['logger.flash']->warning($msg);

        // Have a file, but not writable
        } elseif (file_exists($this->absPath) && !is_writable($this->absPath)) {
            $msg = Trans::__(
                'general.phrase.file-not-writable',
                $msgRepl
            );
            $this->app['logger.flash']->warning($msg);

        // File is not readable: abort
        } elseif (file_exists($this->absPath) && !is_readable($this->absPath)) {
            $msg = Trans::__('general.phrase.error-translation-file-not-readable', $msgRepl);
            $this->app->abort(Response::HTTP_NOT_FOUND, $msg);

        // File is writeable
        } else {
            return true;
        }

        return false;
    }
}
