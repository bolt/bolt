<?php

namespace Bolt\Composer;

use Bolt;
use Bolt\Collection\Arr;
use Bolt\Common\Json;
use Bolt\Filesystem\Exception\IOException;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Translation\Translator as Trans;
use Silex\Application;
use Webmozart\PathUtil\Path;

/**
 * Composer JSON file manager class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class JsonManager
{
    /** @var array */
    protected $messages = [];

    /** @var Application */
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
        $this->app['filesystem']->write($file, Json::dump($data, Json::HUMAN));
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
                $this->messages[] = Trans::__('page.extend.error-composer-json-not-readable');

                $this->app['extend.writeable'] = false;
                $this->app['extend.online'] = false;

                return null;
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
                $this->messages[] = Trans::__('page.extend.error-composer-json-not-writable');
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
        $config = $this->app['config'];
        $pathResolver = $this->app['path_resolver'];

        $rootPath = $pathResolver->resolve('root');
        $extensionsPath = $pathResolver->resolve('extensions');
        $webPath = $pathResolver->resolve('web');
        $pathToRoot = Path::makeRelative($rootPath, $extensionsPath);
        $pathToWeb = Path::makeRelative($webPath, $extensionsPath);
        $pathToListeners = Path::makeRelative(__DIR__ . '/EventListener', $extensionsPath);
        /** @deprecated Handle BC on 'stability' key until 4.0 */
        $minimumStability = $config->get('general/extensions/stability') ?: $config->get('general/extensions/composer/minimum-stability', 'stable');

        $config = $config->get('general/extensions/composer/config', []) + [
            'discard-changes'   => true,
            'preferred-install' => 'dist',
        ];

        // Enforce standard settings
        $defaults = [
            'name'         => 'bolt/extensions',
            'description'  => 'Bolt extension installation interface',
            'license'      => 'MIT',
            'repositories' => [
                'packagist' => false,
                'bolt'      => [
                    'type' => 'composer',
                    'url'  => $this->app['extend.site'] . 'satis/',
                ],
            ],
            'minimum-stability' => $minimumStability,
            'prefer-stable'     => true,
            'config'            => $config,
            'provide'           => [
                'bolt/bolt' => Bolt\Version::forComposer(),
            ],
            'extra' => [
                'bolt-web-path'  => $pathToWeb,
                'bolt-root-path' => $pathToRoot,
            ],
            'autoload' => [
                'psr-4' => [
                    'Bolt\\Composer\\EventListener\\' => $pathToListeners,
                ],
            ],
            'scripts' => [
                'post-autoload-dump'   => 'Bolt\\Composer\\EventListener\\PackageEventListener::dump',
                'post-package-install' => 'Bolt\\Composer\\EventListener\\PackageEventListener::handle',
                'post-package-update'  => 'Bolt\\Composer\\EventListener\\PackageEventListener::handle',
            ],
        ];
        $json = Arr::replaceRecursive($json, $defaults);
        ksort($json);

        return $json;
    }
}
