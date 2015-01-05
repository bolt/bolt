<?php

namespace Bolt\Composer;

use Bolt\Composer\Action\DumpAutoload;
use Composer\Factory;
use Composer\IO\BufferIO;

class PackageManager
{
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
     * @var Bolt\Composer\Action\DumpAutoload
     */
    private $dumpautoload;

    /**
     *
     */
    public function __construct()
    {
        // Get default options
        $this->getOptions();

        // Create the IO
        $this->io = new BufferIO();

        // Use the factory to get a new Composer object
        $this->composer = Factory::create($this->io, $this->options['composerjson'], true);
    }

    /**
     * Return the output from the last IO
     *
     * @return array
     */
    public function getOutput()
    {
        return $this->io->getOutput();
    }

    /**
     * Dump fresh autoloader
     */
    public function dumpautoload()
    {
        if (!$this->dumpautoload) {
            $this->dumpautoload = new DumpAutoload($this->io, $this->composer, $this->options);
        }

        $this->dumpautoload->execute();
    }

    /**
     * Set the default options
     */
    private function getOptions()
    {
        $this->options = array(
            'composerjson'           => 'composer.json',

            'dryrun'                 => null,    // dry-run              - Outputs the operations but will not execute anything (implicitly enables --verbose)
            'verbose'                => true,    // verbose              - Shows more details including new commits pulled in when updating packages
            'nodev'                  => null,    // no-dev               - Disables installation of require-dev packages
            'noautoloader'           => null,    // no-autoloader        - Skips autoloader generation
            'noscripts'              => null,    // no-scripts           - Skips the execution of all scripts defined in composer.json file
            'withdependencies'       => true,    // with-dependencies    - Add also all dependencies of whitelisted packages to the whitelist
            'ignoreplatformreqs'     => null,    // ignore-platform-reqs - Ignore platform requirements (php & ext- packages)
            'preferstable'           => null,    // prefer-stable        - Prefer stable versions of dependencies
            'preferlowest'           => null,    // prefer-lowest        - Prefer lowest versions of dependencies

            'sortpackages'           => true,    // sort-packages        - Sorts packages when adding/updating a new dependency
            'prefersource'           => false,   // prefer-source        - Forces installation from package sources when possible, including VCS information
            'preferdist'             => true,    // prefer-dist          - Forces installation from package dist even for dev versions
            'update'                 => true,    // [Custom]             - Do package update as well
            'noupdate'               => null,    // no-update            - Disables the automatic update of the dependencies
            'updatenodev'            => true,    // update-no-dev        - Run the dependency update with the --no-dev option
            'updatewithdependencies' => true,    // update-with-dependencies - Allows inherited dependencies to be updated with explicit dependencies

            'dev'                    => null,    // dev - Add requirement to require-dev
                                                 //       Removes a package from the require-dev section
                                                 //       Disables autoload-dev rules

            'onlyname'              => true,     // only-name - Search only in name
            'tokens'                => array(),  // tokens    - Tokens to search for

            'optimizeautoloader'    => true,     // optimize-autoloader - Optimizes PSR0 and PSR4 packages to be loaded with classmaps too, good for production.

            'self'                  => null,     // self       - Show the root package information
            'platform'              => null,     // platform   - List platform packages only
            'installed'             => null,     // installed  - List installed packages only
            'available'             => null,     // available  - List available packages only
            'package'               => null,     // package    - Package to inspect
        );
    }
}
