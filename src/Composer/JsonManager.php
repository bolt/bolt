<?php

namespace Bolt\Composer;

use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Helpers\Arr;
use Bolt\Translation\Translator as Trans;
use Silex\Application;

/**
 * Composer JSON file manager class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class JsonManager
{
    /** @var array */
    protected $messages = [];

    /** @var Application  */
    private $app;

    /**
     * Constructor.
     *
     * @param $app \Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Initialise a JSON file at given location with optional data input.
     *
     * @param string $file
     * @param array  $data
     */
    public function init($file, array $data = null)
    {
        if ($data === null) {
            $data = $this->setJsonDefaults([]);
        }
        $this->app['filesystem']->write($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Set up Composer JSON file.
     *
     * @return array|null
     */
    public function update()
    {
        /** @var \Bolt\Filesystem\Handler\JsonFile $jsonFile */
        $jsonFile = $this->app['filesystem']->get('extensions://composer.json', new JsonFile());

        if (!$jsonFile->exists()) {
            try {
                $this->init('extensions://composer.json');
            } catch (IOException $e) {
                $this->messages[] = Trans::__("The Bolt extensions composer.json isn't readable.");

                $this->app['extend.writeable'] = false;
                $this->app['extend.online'] = false;

                return;
            }
        }

        $json = $jsonOrig = $jsonFile->parse();

        // Workaround Bolt 2.0 installs with "require": []
        if (isset($json['require']) && empty($json['require'])) {
            unset($json['require']);
        }

        $json = $this->setJsonDefaults($json);

        // Write out the file, but only if it's actually changed, and if it's writable.
        if ($json != $jsonOrig) {
            try {
                $jsonFile->dump($json);
            } catch (IOException $e) {
                $this->messages[] = Trans::__("The Bolt extensions composer.json isn't writable.");
            }
        }

        return $json;
    }

    /**
     * Enforce the default JSON settings.
     *
     * @param array $json
     *
     * @return array
     */
    private function setJsonDefaults(array $json)
    {
        $extensionsPath = $this->app['resources']->getPath('extensions');
        $webPath = $this->app['resources']->getPath('web');
        $pathToWeb = $this->app['resources']->findRelativePath($extensionsPath, $webPath);

        // Enforce standard settings
        $defaults = [
            'repositories' => [
                'packagist' => false,
                'bolt'      => [
                    'type' => 'composer',
                    'url'  => $this->app['extend.site'] . 'satis/',
                ],
            ],
            'minimum-stability' => $this->app['config']->get('general/extensions/stability', 'stable'),
            'prefer-stable'     => true,
            'config'            => [
                'discard-changes'   => true,
                'preferred-install' => 'dist',
            ],
            'provide' => [
                'bolt/bolt' => $this->app['bolt_version'],
            ],
            'extra' => [
                'bolt-web-path' => $pathToWeb,
            ],
            'autoload' => [
                'psr-4' => [
                    'Bolt\\Composer\\EventListener\\' => $this->app['resources']->getPath('src/Composer/EventListener'),
                ],
            ],
            'scripts' => [
                'post-package-install' => 'Bolt\\Composer\\EventListener\\PackageEventListener::handle',
                'post-package-update'  => 'Bolt\\Composer\\EventListener\\PackageEventListener::handle',
            ],
        ];
        $json = Arr::mergeRecursiveDistinct($json, $defaults);
        ksort($json);

        return $json;
    }
}
