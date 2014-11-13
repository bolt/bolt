<?php

namespace Bolt\Translation;

use Silex;

/**
 * Generates translations keys for contenttypes
 */
class ContenttypesKeygen
{
    /**
     * Injected Application object
     *
     * @var Silex\Application
     */
    private $app;

    /**
     * Hints for translations
     *
     * @var array
     */
    private $hints;


    /**
     * Hints for translations
     *
     * @var array
     */
    private $translation;

    /**
     * Translations read from file
     *
     * @var array
     */
    private $saved;

    /**
     * Translatable strings
     *
     * @var array
     */
    private $translatables;

    /**
     * Translated contenttype names
     *
     * @var array
     */
    private $ctnames;

    /**
     * Constructor
     *
     * @param Silex\Application $app
     * @param array $translatables
     * @param array $saved
     */
    public function __construct(Silex\Application $app, array $translatables, array $savedTranslations)
    {
        $this->hints = array();
        $this->translation = array();
        $this->ctnames = array();
        $this->app = $app;
        $this->translatables = $translatables;
        $this->saved = $savedTranslations;
    }

    /**
     * Returns translations
     *
     * @return array
     */
    public function translations()
    {
        return $this->translation;
    }

    /**
     * Returns hints
     *
     * @return array
     */
    public function hints()
    {
        return $this->hints;
    }

    /**
     * Generates translations keys for contenttypes
     */
    public function generate()
    {
        $this->generateNamesDescription();
        $this->generateGroups();
        $this->generateFromGeneric();
    }

    /**
     * Generates keys for contenttypes names and description and save found names for later usage
     */
    private function generateNamesDescription()
    {
        foreach ($this->app['config']->get('contenttypes') as $ctname => $ctype) {
            $keyprefix = 'contenttypes.' . $this->slugifyKey($ctname) . '.';

            // Names & description
            $setkeys = array(
                'name.plural' => 'name',
                'name.singular' => 'singular_name',
                'description' => 'description',
            );
            foreach ($setkeys as $setkey => $getkey) {
                $key = $keyprefix . $setkey;

                if ($this->isSaved($key)) {
                    $this->translation[$key] = $this->saved[$key];
                } else {
                    if (isset($ctype[$getkey]) && $ctype[$getkey] !== '') {
                        $this->hints[$key] = $ctype[$getkey];
                    } else {
                        $fallback = $this->fallback($key);
                        if ($fallback !== false) {
                            $this->hints[$key] = $fallback;
                        }
                    }
                    $this->translation[$key] = '';
                }
                // Remember names for later usage
                if ($setkey == 'name.plural') {
                    $this->ctnames[$ctname]['%contenttypes%'] = $this->translation[$key];
                } elseif ($setkey == 'name.singular') {
                    $this->ctnames[$ctname]['%contenttype%'] = $this->translation[$key];
                }
            }
        }
    }

    /**
     * Generates keys for tab group names
     */
    private function generateGroups()
    {
        foreach ($this->app['config']->get('contenttypes') as $ctname => $ctype) {
            $keyprefix = 'contenttypes.' . $this->slugifyKey($ctname) . '.group.';

            if (isset($ctype['groups'])) {
                foreach ($ctype['groups'] as $groupname) {
                    $key = $keyprefix . $this->slugifyKey($groupname);

                    if ($this->isSaved($key)) {
                        $this->translation[$key] = $this->saved[$key];
                    } else {
                        $fallback = $this->fallback($key);
                        $this->hints[$key] = ($fallback !== false) ? $fallback : ucfirst($groupname);
                        $this->translation[$key] = '';
                    }
                }
            }
        }
    }

    /**
     * Generates strings for contenttypes from generic translations
     */
    private function generateFromGeneric()
    {
        $ctypes = $this->app['config']->get('contenttypes');

        foreach (array_keys($this->translatables) as $key) {
            if (substr($key, 0, 21) === 'contenttypes.generic.') {
                foreach ($ctypes as $ctname => $ctype) {
                    $setkey = 'contenttypes.' . $ctname . '.text.' . substr($key, 21);
                    $this->translation[$setkey] = isset($this->saved[$setkey]) ? $this->saved[$setkey] : '';
                    if ($this->translation[$setkey] === '') {
                        $generic = $this->app['translator']->trans($key);
                        if ($generic != $key) {
                            foreach ($this->ctnames[$ctname] as $placeholder => $replace) {
                                $generic = str_replace($placeholder, $replace, $generic);
                            }
                            $this->hints[$setkey] = $generic;
                        }
                    }
                }
            }
        }
    }

    /**
     * Only allow "a-z_" in key parts
     *
     * @param string $key
     * @return string
     */
    private function slugifyKey($key)
    {
        return preg_replace('/[^a-z_]/u', '', strtolower($key));
    }

    /**
     * Test if a translation already exists for a key
     *
     * @param string $key
     * @return bool
     */
    private function isSaved($key)
    {
        return (isset($this->saved[$key]) && $this->saved[$key] !== '');
    }

    /**
     * Returns a fallback translation for a key or false if none can be found
     *
     * @param string $key
     * @return mixed
     */
    private function fallback($key)
    {
        $fallback = $this->app['translator']->trans($key, array(), 'contenttypes');

        return ($fallback === $key) ? false : $fallback;
    }
}
