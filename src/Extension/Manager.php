<?php

namespace Bolt\Extension;

use Bolt\Composer\EventListener\PackageDescriptor;
use Bolt\Config;
use Bolt\Filesystem\Exception\FileNotFoundException;
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
    private $loaded = false;
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
        if ($this->loaded) {
            throw new \RuntimeException(Trans::__('Extensions already loaded.'));
        }

        // Include the extensions autoload file
        if ($this->filesystem->has('vendor/autoload.php') === false) {
            $this->loaded = true;

            return;
        }
        $this->filesystem->includeFile('vendor/autoload.php');

        // Load managed extensions
        $this->loadExtensions();

        $this->loaded = true;
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
     *
     * @return ResolvedExtension
     */
    private function setResolved(ExtensionInterface $extension, $internalName)
    {
        $this->extensions[$internalName] = new ResolvedExtension($extension);
        $this->map[$extension->getName()] = $internalName;

        return $this->extensions[$internalName];
    }

    /**
     * Call register() for each extension.
     *
     * @throws \RuntimeException
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
     * Load the extension autoload.json cache file and build the PackageDescriptor array.
     *
     * @return PackageDescriptor[]
     */
    private function loadCache()
    {
        $descriptors = [];
        try {
            /** @var JsonFile $autoload */
            $autoload = $this->filesystem->get('vendor/autoload.json');
        } catch (FileNotFoundException $e) {
            return $descriptors;
        }

        // Get extensions we're managing via the autoloader
        foreach ((array) $autoload->parse() as $name => $loader) {
            $descriptors[$name] = PackageDescriptor::create($loader);
        }

        return $descriptors;
    }

    /**
     * Load managed extensions.
     */
    private function loadExtensions()
    {
        $descriptors = $this->loadCache();
        foreach ($descriptors as $descriptor) {
            $composerName = $descriptor->getName();
            if ($descriptor->isValid() === false) {
                // Skip loading if marked invalid
                continue;
            }
            $extConfig = $this->config->get('extensions', []);
            if (isset($extConfig[$composerName]) && $extConfig[$composerName] === false) {
                // Skip loading if marked disabled
                continue;
            }
            $this->loadExtension($descriptor);
        }
    }

    /**
     * Load a single extension.
     *
     * @param PackageDescriptor $descriptor
     */
    private function loadExtension(PackageDescriptor $descriptor)
    {
        $className = $descriptor->getClass();
        if (class_exists($className) === false) {
            if ($descriptor->getType() === 'local' && class_exists('Wikimedia\Composer\MergePlugin') === false) {
                $this->flashLogger->error(Trans::__("Local extension set up incomplete. Please run 'Install all packages' on the Extensions page.", ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
            } else {
                $this->flashLogger->error(Trans::__("Extension package %NAME% has an invalid class '%CLASS%' and has been skipped.", ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
            }

            return;
        }

        /** @var ExtensionInterface $extension */
        $extension = new $className();
        if ($extension instanceof ExtensionInterface) {
            $this->setResolved($extension, $descriptor->getName())->setDescriptor($descriptor);
        } else {
            $this->flashLogger->error(Trans::__("Extension package %NAME% base class '%CLASS%' does not implement \\Bolt\\Extension\\ExtensionInterface and has been skipped.", ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
        }
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
