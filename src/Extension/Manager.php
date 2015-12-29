<?php

namespace Bolt\Extension;

use Bolt\Config;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Exception\IncludeFileException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Legacy\ExtensionsTrait;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Translation\Translator as Trans;
use Cocur\Slugify\Slugify;
use Silex\Application;

/**
 * Class to manage loading of extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    use ExtensionsTrait;

    /** @var ResolvedExtension[] */
    protected $extensions = [];
    /** @var string[] */
    protected $map = [];
    /** @var array */
    protected $autoload;

    /** @var FilesystemInterface */
    private $filesystem;
    /** @var FlashLoggerInterface */
    private $flashLogger;
    /** @var Config */
    private $config;
    /** @var bool */
    private $registered = false;
    /** @var Application @deprecated */
    private $app;

    /**
     * Constructor.
     *
     * @param FilesystemInterface  $filesystem
     * @param FlashLoggerInterface $flashLogger
     * @param Config               $config
     */
    public function __construct(FilesystemInterface $filesystem, FlashLoggerInterface $flashLogger, Config $config)
    {
        $this->filesystem = $filesystem;
        $this->flashLogger = $flashLogger;
        $this->config = $config;
    }

    /**
     * Load a collection of extension classes.
     */
    public function load()
    {
        try {
            /** @var JsonFile $autoloadJson */
            $autoloadJson = $this->filesystem->get('vendor/autoload.json');
            $this->filesystem->includeFile('vendor/autoload.php');
        } catch (FileNotFoundException $e) {
            return;
        } catch (IncludeFileException $e) {
            return;
        }

        // Load extensions we're managing via the autoloader
        $this->autoload = (array) $autoloadJson->parse();
        foreach ($this->autoload as $loader) {
            $composerName = $loader['name'];
            if ($loader['valid'] === false) {
                // Skip loading if marked invalid
                continue;
            }
            $extConfig = $this->config->get('extensions', []);
            if (isset($extConfig[$composerName]) && $extConfig[$composerName] === false) {
                // Skip loading if marked disabled
                continue;
            }
            $this->loadExtension($loader['class'], $composerName);
        }
    }

    /**
     * Load a single extension.
     *
     * @param string $className
     * @param string $composerName
     */
    private function loadExtension($className, $composerName)
    {
        if (class_exists($className) === false) {
            $this->flashLogger->error(Trans::__("Extension package %NAME% has an invalid class '%CLASS%' and has been skipped.", ['%NAME%' => $composerName, '%CLASS%' => $className]));

            return;
        }

        /** @var ExtensionInterface $extension */
        $extension = new $className();
        if ($extension instanceof ExtensionInterface) {
            $this->setResolved($extension, $composerName);
        } else {
            $this->flashLogger->error(Trans::__("Extension package %NAME% base class '%CLASS%' does not implement \\Bolt\\Extension\\ExtensionInterface and has been skipped.", ['%NAME%' => $composerName, '%CLASS%' => $className]));
        }
    }

    /**
     * Insert an extension.
     *
     * This should only be used during bootstrap… You probably don't want to use this function.
     *
     * @param ExtensionInterface $extension
     */
    public function add(ExtensionInterface $extension)
    {
        $internalName = Slugify::create()->slugify($extension->getVendor() . '/' . $extension->getName(), '/');
        $this->setResolved($extension, $internalName);
    }

    /**
     * Return the generated autoloading cache.
     *
     * @return array
     */
    public function getAutoload()
    {
        return $this->autoload;
    }

    /**
     * Get all installed extensions.
     *
     * @return ResolvedExtension[]
     */
    public function all()
    {
        return $this->extensions;
    }

    /**
     * Get an installed extension class.
     *
     * @param string|null $name
     *
     * @return ExtensionInterface|null
     */
    public function get($name)
    {
        if ($key = $this->getMappedKey($name)) {
            return $this->extensions[$key]->getInnerExtension();
        }
    }

    /**
     * Get the resolved form of an installed extension class.
     *
     * @param string|null $name
     *
     * @return ResolvedExtension|null
     */
    public function getResolved($name)
    {
        if ($key = $this->getMappedKey($name)) {
            return $this->extensions[$key];
        }
    }

    /**
     * Add an extension as resolved to the class.
     *
     * @param ExtensionInterface $extension
     * @param string             $internalName
     */
    private function setResolved(ExtensionInterface $extension, $internalName)
    {
        $this->extensions[$internalName] = new ResolvedExtension($extension);
        $this->map[$extension->getName()] = $internalName;
    }

    /**
     * Call register() for each extension.
     *
     * @param Application $app
     */
    public function register(Application $app)
    {
        if ($this->registered) {
            throw new \RuntimeException(Trans::__('Can not re-register extensions.'));
        }
        foreach ($this->extensions as $extension) {
            $extension->getInnerExtension()->setContainer($app);
            $app->register($extension->getInnerExtension()->getServiceProvider());
        }
        $this->registered = true;

        // @deprecated Deprecated since 3.0, to be removed in 4.0.
        $this->app = $app;
    }

    /**
     * Return the in-use extension name map.
     *
     * @return string[]
     */
    public function getMap()
    {
        return $this->map;
    }

    /**
     * Resolve a Composer or PHP extension name to the stored key.
     *
     * @param string $name
     *
     * @return string|null
     */
    private function getMappedKey($name)
    {
        $key = null;
        if (isset($this->map[$name])) {
            $key = $this->map[$name];
        } elseif (isset($this->extensions[$name])) {
            $key = $name;
        }

        return $key;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @internal Do not use! For legacy support only.
     */
    protected function getApp()
    {
        return $this->app;
    }
}
