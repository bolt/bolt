<?php

namespace Bolt;

use Silex;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Escaper;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

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
     * Get the path to a tranlsation resource
     *
     * @param string $domain Requested resource
     * @param string $locale Requested locale
     * @param bool $short If true just return project relative path
     * @return string
     */
    public function path($domain, $locale, $short = false)
    {
        $shortLocale = substr($locale, 0, 2);
        $path = ($short ? 'app' : $this->app['paths']['apppath']) . '/resources/translations/' . $shortLocale;

        return $path . '/' . $domain . '.' . $shortLocale . '.yml';
    }

    /**
     * Get the project relative path to a tranlsation resource
     *
     * @param string $domain Requested resource
     * @param string $locale Requested locale
     * @return string
     */
    public function shortPath($domain, $locale)
    {
        return $this->path($domain, $locale, true);
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
     *  Add fields names and labels for contenttype (forms) to the list of translatable strings
     */
    private function scanContenttypeFields()
    {
        foreach ($this->app['config']->get('contenttypes') as $contenttype) {
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
     *  Add relation names and labels to the list of translatable strings
     */
    private function scanContenttypeRelations()
    {
        foreach ($this->app['config']->get('contenttypes') as $contenttype) {
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
     * @param type $locale
     * @param array $translated
     * @param array $getMessages True returns translation datat for messages, false for contenttypes
     * @return array
     */
    private function gatherTranslatableStrings($locale, $translated, $getMessages)
    {
        // Step 1: Gather all translatable strings

        $this->translatables = array();

        $this->scanTwigFiles();
        $this->scanPhpFiles();
        $this->scanContenttypeFields();
        $this->scanContenttypeRelations();
        $this->scanTaxonomies();

        // Build lists

        $msgTranslated = array();
        $msgUntranslated = array();

        foreach ($this->translatables as $key) {
            $keyRaw = stripslashes($key);
            $keyEsc = Escaper::escapeWithDoubleQuotes($keyRaw);
            if ($getMessages) {
                // Step 2: Find already translated strings
                if (($trans = $this->getTranslated($keyRaw, $translated)) == '' &&
                    ($trans = $this->getTranslated($keyEsc, $translated)) == ''
                ) {
                    $msgUntranslated[] = $keyEsc;
                } else {
                    $trans = Escaper::escapeWithDoubleQuotes($trans);
                    $msgTranslated[$keyEsc] = $trans;
                }
            } else {
                // Step 3: Generate additional strings for contenttypes
                if (strpos($keyRaw, '%contenttype%') !== false || strpos($keyRaw, '%contenttypes%') !== false) {
                    foreach ($this->genContentTypes($keyRaw) as $ctypekey) {
                        $keyEsc = Escaper::escapeWithDoubleQuotes($ctypekey);
                        if (($trans = $this->getTranslated($ctypekey, $translated)) == '' &&
                            ($trans = $this->getTranslated($keyEsc, $translated)) == ''
                        ) {
                            $msgUntranslated[] = $keyEsc; // Not translated
                        } else {
                            $msgTranslated[$keyEsc] = Escaper::escapeWithDoubleQuotes($trans);
                        }
                    }
                }
            }
        }

        sort($msgUntranslated);
        ksort($msgTranslated);

        return array($msgTranslated, $msgUntranslated);
    }

    /**
     * Get the content of the info translation file or the fallback file
     *
     * @param string $locale Wanted locale
     * @return string
     */
    public function getInfoContent($locale)
    {
        $path = $this->path('infos', $locale);

        // No gathering here: if the file doesn't exist yet, we load a copy from the locale_fallback version (en)
        if (!file_exists($path) || filesize($path) < 10) {
            $path = $this->path('infos', 'en');
        }

        return file_get_contents($path);
    }

    /**
     * Gets all translatable strings and returns a translationsfile for messages or contenttypes
     *
     * @param string $domain
     * @param string $locale
     * @return string
     */
    public function getContent($domain, $locale)
    {
        $path = $this->path($domain, $locale);

        $translated = array();
        if (is_file($path) && is_readable($path)) {
            try {
                $translated = Yaml::parse($path);
            } catch (ParseException $e) {
                $app['session']->getFlashBag()->set('error', printf('Unable to parse the YAML translations: %s', $e->getMessage()));
            }
        }

        list($msgTranslated, $msgUntranslated) = $this->gatherTranslatableStrings($locale, $translated, $domain == 'messages');

        $content = '# ' . $this->shortPath($domain, $locale) . ' -- generated on ' . date('Y/m/d H:i:s') . "\n";

        $cnt = count($msgUntranslated);
        if ($cnt) {
            $content .= '# ' . $cnt . ' untranslated strings' . "\n\n";
            foreach ($msgUntranslated as $key) {
                $content .= $key . ':  #' . "\n";
            }
            $content .= "\n" . '#-----------------------------------------' . "\n";
        } else {
            $content .= '# no untranslated strings' . "\n\n";
        }
        $content .= '# ' . count($msgTranslated) . ' translated strings' . "\n\n";
        foreach ($msgTranslated as $key => $trans) {
            $content .= $key . ': ' . $trans . "\n";
        }

        return $content;
    }

    /**
     * Checks if translations file is allowed to write to
     *
     * @param string $domain
     * @param string $locale
     * @return bool
     */
    public function isWriteAllowed($domain, $locale)
    {
        $path = $this->path($domain, $locale);
        $msgRepl = array('%s' => $this->shortPath($domain, $locale));

        // No translations yet: info
        if (!file_exists($path) && !is_writable(dirname($path))) {
            $msg = __(
                "The translations file '%s' can't be created. You will have to use your own editor to make modifications to this file.",
                $msgRepl
            );
            $this->app['session']->getFlashBag()->set('info', $msg);
        // File is not readable: abort
        } elseif (file_exists($path) && !is_readable($path)) {
            $msg = __("The translations file '%s' is not readable.", $msgRepl);
            $this->app->abort(404, $msg);
        // File is not writeable: warning
        } elseif (!is_writable($path)) {
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
