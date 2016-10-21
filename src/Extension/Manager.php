<?php

namespace Bolt\Extension;

use Bolt\Composer\EventListener\PackageDescriptor;
use Bolt\Config;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Translation\LazyTranslator as Trans;
use Silex\Application;
use Symfony\Component\Debug\Exception\ContextErrorException;

/**
 * Class to manage loading of extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    /** @var ResolvedExtension[] */
    protected $extensions = [];
    /** @var string[] */
    protected $composerNames = [];

    /** @var FilesystemInterface */
    private $extFs;
    /** @var FilesystemInterface */
    private $webFs;
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
     * @param FilesystemInterface  $extensions
     * @param FilesystemInterface  $web
     * @param FlashLoggerInterface $flashLogger
     * @param Config               $config
     */
    public function __construct(FilesystemInterface $extensions, FilesystemInterface $web, FlashLoggerInterface $flashLogger, Config $config)
    {
        $this->extFs = $extensions;
        $this->webFs = $web;
        $this->flashLogger = $flashLogger;
        $this->config = $config;
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
     * @param string|null $id The extension ID or composer name
     *
     * @return ExtensionInterface|null
     */
    public function get($id)
    {
        $resolved = $this->getResolved($id);

        return $resolved ? $resolved->getInnerExtension() : null;
    }

    /**
     * Get the resolved form of an installed extension class.
     *
     * @param string|null $id The extension ID or composer name
     *
     * @return ResolvedExtension|null
     */
    public function getResolved($id)
    {
        if (isset($this->extensions[$id])) {
            return $this->extensions[$id];
        } elseif (isset($this->composerNames[$id])) {
            $id = (string) $this->composerNames[$id];

            return $this->extensions[$id];
        }

        return null;
    }

    /**
     * Add an extension to be registered.
     *
     * @param ExtensionInterface      $extension
     * @param DirectoryInterface|null $baseDir
     * @param DirectoryInterface|null $webDir
     * @param string|null             $composerName
     *
     * @throws \RuntimeException
     *
     * @return ResolvedExtension
     */
    public function add(ExtensionInterface $extension, DirectoryInterface $baseDir = null, DirectoryInterface $webDir = null, $composerName = null)
    {
        if ($this->registered) {
            throw new \RuntimeException('Can not add extensions after they are registered.');
        }

        // Set paths in the extension
        if ($baseDir !== null) {
            $extension->setBaseDirectory($baseDir);
        }
        if ($webDir !== null) {
            $extension->setWebDirectory($webDir);
        }

        // Determine if enabled
        $enabled = $this->config->get('extensions/' . $extension->getId(), true);

        if ($composerName !== null) {
            // Map composer name to ID
            $this->composerNames[$composerName] = $extension->getId();

            // Check if enabled by composer name
            $enabled = $this->config->get("extensions/$composerName", $enabled);
        }

        // Instantiate resolved extension and mark enabled/disabled
        $resolved = (new ResolvedExtension($extension))
            ->setEnabled($enabled)
        ;

        return $this->extensions[$extension->getId()] = $resolved;
    }

    /**
     * Load a collection of extension classes.
     */
    public function addManagedExtensions()
    {
        if ($this->loaded) {
            throw new \RuntimeException('Extensions already loaded.');
        }

        try {
            $this->extFs->includeFile('vendor/autoload.php');
        } catch (FileNotFoundException $e) {
            $this->loaded = true;

            return;
        }

        $descriptors = $this->loadPackageDescriptors();
        foreach ($descriptors as $descriptor) {
            // Skip loading if marked invalid
            if ($descriptor->isValid() === false) {
                continue;
            }
            $this->addManagedExtension($descriptor);
        }

        $this->loaded = true;
    }

    /**
     * Call register() for each extension.
     *
     *
     * @param Application $app
     *
     * @throws \RuntimeException
     */
    public function register(Application $app)
    {
        if ($this->registered) {
            throw new \RuntimeException('Can not re-register extensions.');
        }
        foreach ($this->extensions as $extension) {
            if ($extension->isEnabled() !== true) {
                continue;
            }
            $extension->getInnerExtension()->setContainer($app);
            foreach ($extension->getInnerExtension()->getServiceProviders() as $provider) {
                $app->register($provider);
            }
        }
        $this->registered = true;

        // @deprecated Deprecated since 3.0, to be removed in 4.0.
        $this->app = $app;
    }

    /**
     * Load the extension autoload.json cache file and build the PackageDescriptor array.
     *
     * @return PackageDescriptor[]
     */
    private function loadPackageDescriptors()
    {
        $descriptors = [];
        try {
            /** @var JsonFile $autoload */
            $autoload = $this->extFs->get('vendor/autoload.json');
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
     * Load a single extension.
     *
     * @param PackageDescriptor $descriptor
     */
    private function addManagedExtension(PackageDescriptor $descriptor)
    {
        $className = $descriptor->getClass();
        if ($this->isClassLoadable($className) === false) {
            if ($descriptor->getType() === 'local' && $this->isClassLoadable('Wikimedia\Composer\MergePlugin') === false) {
                $this->flashLogger->error(Trans::__('general.phrase.error-local-extension-set-up-incomplete', ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
            } else {
                $this->flashLogger->error(Trans::__("Extension package %NAME% has an invalid class '%CLASS%' and has been skipped.", ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
            }

            return;
        }

        /** @var ExtensionInterface $extension */
        $extension = new $className();
        if ($extension instanceof ExtensionInterface) {
            $baseDir = $this->extFs->getDir($descriptor->getPath());
            $webDir = $this->webFs->getDir($descriptor->getWebPath());
            $this->add($extension, $baseDir, $webDir, $descriptor->getName())
                ->setDescriptor($descriptor)
            ;
        } else {
            $this->flashLogger->error(Trans::__("Extension package %NAME% base class '%CLASS%' does not implement \\Bolt\\Extension\\ExtensionInterface and has been skipped.", ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
        }
    }

    /**
     * Check if a class is loadable.
     *
     * This comes about as local extensions that are moved or removed will over
     * emmit warnings while trying to validly rebuild autoloaders.
     *
     * @param string $className
     *
     * @throws ContextErrorException
     *
     * @return bool
     */
    private function isClassLoadable($className)
    {
        try {
            $exists = class_exists($className);
        } catch (ContextErrorException $e) {
            if ($e->getSeverity() === E_WARNING && basename($e->getFile()) === 'ClassLoader.php') {
                return false;
            }
            throw $e;
        }

        return $exists;
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
