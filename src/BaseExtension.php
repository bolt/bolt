<?php
namespace Bolt;

use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Widget\Widget;
use Bolt\Extension\SimpleExtension;
use Bolt\Extensions\AssetTrait;
use Bolt\Extensions\ExtensionInterface;
use Bolt\Extensions\TwigProxy;
use Bolt\Response\BoltResponse;
use Composer\Json\JsonFile;
use Symfony\Component\Yaml;

/**
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 */
abstract class BaseExtension extends SimpleExtension
{
    public $config;

    protected $app;
    /** @var TwigProxy */
    protected $twigExtension;
    protected $installtype = 'composer';

    private $extensionConfig;
    private $composerJsonLoaded;
    private $composerJson;

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    abstract protected function initialize();

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
     * Gets a 'machine name' for this extension.
     * The machine name is the composer name, if available, or a slugified
     * version of the name as reported by getName() otherwise.
     *
     * @return string
     */
    public function getMachineName()
    {
        $composerName = $this->getComposerName();
        if (empty($composerName)) {
            return $this->app['slugify']->slugify($this->getName());
        } else {
            return $composerName;
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

    protected function initializeTwig()
    {
        if (!$this->twigExtension) {
            $this->twigExtension = new TwigProxy($this->getName());
        }
    }

    public function getTwigExtensions()
    {
        if ($this->twigExtension) {
            return [$this->twigExtension];
        }

        return [];
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
     * Insert a snippet into the generated HTML.
     *
     * @param string $location
     * @param string $callback
     * @param array  $callbackArguments
     */
    public function addSnippet($location, $callback, $callbackArguments = [])
    {
        if ($callback instanceof BoltResponse) {
            $callback = (string) $callback;
        }

        // If we pass a callback as a simple string, we need to turn it into an array.
        if (is_string($callback) && method_exists($this, $callback)) {
            $callback = [$this, $callback];
        }

        $snippet = (new Snippet())
            ->setLocation($location)
            ->setCallback($callback)
            ->setExtension($this->getName())
            ->setCallbackArguments((array) $callbackArguments)
        ;

        $this->getApp()['asset.queue.snippet']->add($snippet);
    }

    /**
     * Make sure jQuery is added.
     */
    public function addJquery()
    {
        $this->app['extensions']->addJquery();
    }

    /**
     * Don't make sure jQuery is added. Note that this does not mean that jQuery will _not_ be added.
     * It only means that the extension will not add it, but others still might do so.
     */
    public function disableJquery()
    {
        $this->app['extensions']->disableJquery();
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
        return $this->app['asset.queue.file']->clear();
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

    /**
     * Add a Widget to the render queue.
     *
     * @param Widget $widget
     */
    public function addWidget($widget)
    {
        if ($widget instanceof Widget) {
            return $this->app['asset.queue.widget']->add($widget);
        }
        $this->app['logger.system']->error(sprintf('%s tried inserting an invalid widget object. Ignoring.', $this->getName()), ['event' => 'extensions']);
    }
}
