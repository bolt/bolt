<?php

namespace Bolt\Composer\Action;

use Composer\Factory;
use Composer\Installer;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Package\Version\VersionParser;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Silex\Application;

class RequirePackage
{
    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Composer\IO\BufferIO
     */
    private $io;

    /**
     * @var Composer\Composer
     */
    private $composer;

    /**
     * @param $app      Silex\Application
     * @param $io       Composer\IO\BufferIO
     * @param $composer Composer\Composer
     * @param $options  array
     */
    public function __construct(Application $app, $io, $composer, $options)
    {
        $this->app = $app;
        $this->options = $options;
        $this->io = $io;
        $this->composer = $composer;
    }

    /**
     * Require (install) packages
     *
     * @param  $packages array Associative array of package names/versions to remove
     *                         Format: array('name' => '', 'version' => '')
     * @return integer 0 on success or a positive error code on failure
     */
    public function execute(array $packages)
    {
        $file = $this->options['composerjson'];

        $newlyCreated = !file_exists($file);

        if (!file_exists($file) && !file_put_contents($file, "{\n}\n")) {
            // JSON could not be created
            return 1;
        }
        if (!is_readable($file)) {
            // JSON is not readable
            return 1;
        }
        if (!is_writable($file)) {
            // JSON is not writable
            return 1;
        }

        $json = new JsonFile($this->options['composerjson']);
        $composerDefinition = $json->read();
        $composerBackup = file_get_contents($json->getPath());

        $repos = $this->composer->getRepositoryManager()->getRepositories();

        $this->repos = new CompositeRepository(array_merge(
            array(new PlatformRepository()),
            $repos
        ));

        $requireKey = $this->options['dev'] ? 'require-dev' : 'require';
        $removeKey = $this->options['dev'] ? 'require' : 'require-dev';

        $baseRequirements = array_key_exists($requireKey, $composerDefinition) ? $composerDefinition[$requireKey] : array();
        $packages = $this->formatRequirements($packages);
        $sortPackages = $this->options['sortpackages'];

        // validate requirements format
        $versionParser = new VersionParser();
        foreach ($packages as $constraint) {
            $versionParser->parseConstraints($constraint);
        }

        if (!$this->updateFileCleanly($json, $baseRequirements, $packages, $requireKey, $removeKey, $sortPackages)) {
            foreach ($packages as $package => $version) {
                $baseRequirements[$package] = $version;

                if (isset($composerDefinition[$removeKey][$package])) {
                    unset($composerDefinition[$removeKey][$package]);
                }
            }

            $composerDefinition[$requireKey] = $baseRequirements;
            $json->write($composerDefinition);
        }

        // JSON file has been created/updated, if we're not installing, exit
        if ($this->options['noupdate']) {
            return 0;
        }

        // Reload Composer config
        $this->composer = $this->app['extend.runner']->getComposer();

        $install = Installer::create($this->io, $this->composer);

        $install
            ->setVerbose($this->options['verbose'])
            ->setPreferSource($this->options['prefersource'])
            ->setPreferDist($this->options['preferdist'])
            ->setDevMode(!$this->options['updatenodev'])
            ->setUpdate($this->options['update'])
            ->setUpdateWhitelist(array_keys($packages))
            ->setWhitelistDependencies($this->options['updatewithdependencies'])
            ->setIgnorePlatformRequirements($this->options['ignoreplatformreqs'])
        ;

        $status = $install->run();
        if ($status !== 0) {
            if ($newlyCreated) {
                // Installation failed, deleting JSON
                unlink($json->getPath());
            } else {
                // Installation failed, reverting JSON to its original content
                file_put_contents($json->getPath(), $composerBackup);
            }
        }

        return $status;
    }

    protected function formatRequirements(array $packages)
    {
        $requires = array();
        $packages = $this->normalizeRequirements($packages);
        foreach ($packages as $package) {
            $requires[$package['name']] = $package['version'];
        }

        return $requires;
    }

    protected function normalizeRequirements(array $packages)
    {
        $parser = new VersionParser();

        return $parser->parseNameVersionPairs($packages);
    }

    private function updateFileCleanly($json, array $base, array $new, $requireKey, $removeKey, $sortPackages)
    {
        $contents = file_get_contents($json->getPath());

        $manipulator = new JsonManipulator($contents);

        foreach ($new as $package => $constraint) {
            if (!$manipulator->addLink($requireKey, $package, $constraint, $sortPackages)) {
                return false;
            }
            if (!$manipulator->removeSubNode($removeKey, $package)) {
                return false;
            }
        }

        file_put_contents($json->getPath(), $manipulator->getContents());

        return true;
    }
}
