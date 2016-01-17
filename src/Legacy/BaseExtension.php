<?php

namespace Bolt\Legacy;

use Bolt\Extension\SimpleExtension;
use Composer\Json\JsonFile;
use Silex\Application;

/**
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 */
abstract class BaseExtension extends SimpleExtension
{
    public $config;

    protected $app;
    protected $installtype = 'composer';

    private $extensionConfig;
    private $composerJsonLoaded;
    private $composerJson;

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;

        $this->extensionConfig = null;
        $this->composerJsonLoaded = false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getApp()
    {
        return $this->app;
    }

    /**
     * Set the extension install type.
     *
     * @param string $type
     */
    public function setInstallType($type)
    {
        if ($type === 'composer' || $type === 'local') {
            $this->installtype = $type;
        }
    }

    /**
     * Get the extension type.
     *
     * @return string
     */
    public function getInstallType()
    {
        return $this->installtype;
    }

    /**
     * Gets the Composer name, e.g. 'bolt/foobar-extension'.
     *
     * @return string|null The Composer name for this extension, or NULL if the
     *                     extension is not composerized.
     */
    public function getComposerName()
    {
        $composerjson = $this->getComposerJSON();
        if (isset($composerjson['name'])) {
            return $composerjson['name'];
        } else {
            return null;
        }
    }

    /**
     * Get the contents of the extension's composer.json file, lazy-loading
     * as needed.
     */
    public function getComposerJSON()
    {
        if (!$this->composerJsonLoaded && !$this->composerJson) {
            $this->composerJsonLoaded = true;
            $this->composerJson = null;
            $jsonFile = new JsonFile(__DIR__ . '/composer.json');
            if ($jsonFile->exists()) {
                $this->composerJson = $jsonFile->read();
            }
        }

        return $this->composerJson;
    }

    /**
     * This allows write access to the composer config, allowing simulation of this feature
     * even if the extension doesn't have a physical composer.json file.
     *
     * @param array $configuration
     *
     * @return array
     */
    public function setComposerConfiguration(array $configuration)
    {
        $this->composerJsonLoaded = true;
        $this->composerJson = null;
        $this->composerJson = $configuration;

        return $this->composerJson;
    }

    /**
     * Builds an array suitable for conversion to JSON, which in turn will end
     * up in a consolidated JSON file containing the configurations of all
     * installed extensions.
     */
    public function getExtensionConfig()
    {
        if (!is_array($this->extensionConfig)) {
            $composerjson = $this->getComposerJSON();
            if (is_array($composerjson)) {
                $this->extensionConfig = [
                    strtolower($composerjson['name']) => [
                        'name' => $this->getName(),
                        'json' => $composerjson,
                    ],
                ];
            } else {
                $this->extensionConfig = [
                    $this->getName() => [
                        'name' => $this->getName(),
                        'json' => [],
                    ],
                ];
            }
        }

        return $this->extensionConfig;
    }

    /**
     * Allow use of the extension's Twig function in content records when the
     * content type has the setting 'allowtwig: true' is set.
     *
     * @return boolean
     */
    public function isSafe()
    {
        return false;
    }

    /**
     * Return the available Snippets, used in \Bolt\Extensions.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use $app['asset.queue.snippet']->getQueue()
     *
     * @return array
     */
    public function getSnippets()
    {
        $snippets = [];
        foreach ($this->app['asset.queue.snippet']->getQueue() as $snippet) {
            $snippets[] = (string) $snippet;
        }

        return $snippets;
    }

    /**
     * Returns a list of all css and js assets that are added via extensions.
     *
     * @return array
     */
    public function getAssets()
    {
        return $this->app['extensions']->getAssets();
    }

    /**
     * Clear all previously added assets.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function clearAssets()
    {
        $this->app['asset.queue.file']->clear();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use \Bolt\Extension\MenuTrait::addMenuEntry() instead
     */
    public function addMenuOption($label, $path, $icon = null, $requiredPermission = null)
    {
        $this->addMenuEntry($label, $path, $icon, $requiredPermission);
    }

    /**
     * Parse a snippet, an pass on the generated HTML to the caller (Extensions).
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param string $callback
     * @param string $var1
     * @param string $var2
     * @param string $var3
     *
     * @return bool|string
     */
    public function parseSnippet($callback, $var1 = '', $var2 = '', $var3 = '')
    {
        if (method_exists($this, $callback)) {
            return call_user_func([$this, $callback], $var1, $var2, $var3);
        } else {
            return false;
        }
    }
}
