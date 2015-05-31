<?php

namespace Bolt\Composer\Action;

use Bolt\Translation\Translator as Trans;
use Composer\Json\JsonFile;
use Silex\Application;

/**
 * Initialise Composer JSON file class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class BoltExtendJson
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var string[]
     */
    private $messages;

    /**
     * @param $options  array
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    /**
     * Convenience function to generalise the library.
     *
     * @param string $file
     * @param array  $data
     */
    public function execute($file, array $data = array())
    {
        $this->initJson($file, $data);
    }

    /**
     * Initialise a JSON file at given location with optional data input.
     *
     * @param string $file
     * @param array  $data
     */
    public function initJson($file, array $data = array())
    {
        $jsonFile = new JsonFile($file);
        $jsonFile->write($data);
    }

    /**
     * Set up Composer JSON file.
     *
     * @param Application $app
     *
     * @return array|null
     */
    public function updateJson(Application $app)
    {
        if (!is_file($this->options['composerjson'])) {
            $this->initJson($this->options['composerjson']);
        }

        $jsonFile = new JsonFile($this->options['composerjson']);
        if ($jsonFile->exists()) {
            $json = $jsonorig = $jsonFile->read();

            // Workaround Bolt 2.0 installs with "require": []
            if (isset($json['require']) && empty($json['require'])) {
                unset($json['require']);
            }
        } else {
            // Error
            $this->messages[] = Trans::__(
                "The Bolt extensions file '%composerjson%' isn't readable.",
                array('%composerjson%' => $this->options['composerjson'])
            );

            $app['extend.writeable'] = false;
            $app['extend.online'] = false;

            return null;
        }

        $pathToWeb = $app['resources']->findRelativePath($app['resources']->getPath('extensions'), $app['resources']->getPath('web'));

        // Enforce standard settings
        $json['repositories']['packagist'] = false;
        $json['repositories']['bolt'] = array(
            'type' => 'composer',
            'url'  => $app['extend.site'] . 'satis/'
        );
        $json['minimum-stability'] = $app['config']->get('general/extensions/stability', 'stable');
        $json['prefer-stable'] = true;
        $json['config'] = array(
            'discard-changes'   => true,
            'preferred-install' => 'dist'
        );
        $json['provide']['bolt/bolt'] = $app['bolt_version'];
        $json['extra']['bolt-web-path'] = $pathToWeb;
        $json['autoload']['psr-4']['Bolt\\Composer\\'] = '';
        $json['scripts'] = array(
            'post-package-install' => 'Bolt\\Composer\\ExtensionInstaller::handle',
            'post-package-update'  => 'Bolt\\Composer\\ExtensionInstaller::handle'
        );

        // Write out the file, but only if it's actually changed, and if it's writable.
        if ($json != $jsonorig) {
            try {
                umask(0000);
                $jsonFile->write($json);
            } catch (\Exception $e) {
                $this->messages[] = Trans::__(
                    'The Bolt extensions Repo at %repository% is currently unavailable. Check your connection and try again shortly.',
                    array('%repository%' => $app['extend.site'])
                );
            }
        }

        return $json;
    }
}
