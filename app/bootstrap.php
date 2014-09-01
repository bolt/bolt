<?php
namespace Bolt;

use Bolt\Configuration\LowlevelException;
// Do bootstrapping within a new local scope to avoid polluting the global
return call_user_func(function ()
{

    // First ensure load.php was called right before bootstrap.php
    $includes = get_included_files();
    $loaderPath = __DIR__ . DIRECTORY_SEPARATOR . 'load.php';
    $includeCount = count($includes);
    // Should be at least 3 includes at this point:
    // <load-invoker>.php (usually entry point), load.php, bootstrap.php
    // Second to last entry must be load.php
    $isLoadChainOk = $includeCount >= 3 && $includes[$includeCount - 2] == $loaderPath;

    require_once __DIR__ . '/src/Bolt/Configuration/LowlevelException.php';

    if (! $isLoadChainOk) {
        throw new LowlevelException('Include load.php, not bootstrap.php');
    }

    // Bootstrap:

    // TODO: Phase out lib.php
    require_once __DIR__ . '/lib.php';

    // Use UTF-8 for all multi-byte functions
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');

    // Resolve Bolt-root
    $boltRootPath = realpath(__DIR__ . '/..');

    // Look for the autoloader in known positions relative to the Bolt-root,
    // and autodetect an appropriate configuration class based on this
    // information. (autoload.php path maps to a configuration class)
    $autodetectionMappings = array(
        $boltRootPath . '/vendor/autoload.php' => 'Standard',
        $boltRootPath . '/../../autoload.php' => 'Composer'
    );

    foreach ($autodetectionMappings as $autoloadPath => $configType) {
        if (file_exists($autoloadPath)) {
            $loader = require_once $autoloadPath;
            $configClass = '\\Bolt\\Configuration\\' . $configType;
            $config = new $configClass($loader);
            break;
        }
    }

    // None of the mappings matched, error
    if (! isset($config)) {
        throw new LowlevelException("Configuration autodetection failed because The file " .
            "<code>vendor/autoload.php</code> doesn't exist. Make sure " .
            "you've installed the required components with Composer.");
    }

    /**
     * @var $config Configuration\ResourceManager
     */
    $config->verify();
    $config->compat();

    // Create the 'Bolt application'
    $app = new Application(array(
        'resources' => $config
    ));

    // Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
    $app->initialize();

    return $app;
});
