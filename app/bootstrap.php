<?php
namespace Bolt;

use Bolt\Exception\LowlevelException;

/**
 * Second stage loader. Here we bootstrap the app:
 *
 * - Initialize mb functions for UTF-8
 * - Figure out path structure
 * - Bring in the autoloader
 * - Load and verify configuration
 * - Initialize the application
 */

// Do bootstrapping within a new local scope to avoid polluting the global
return call_user_func(
    function () {
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
                $loader = require $autoloadPath;
                $configClass = '\\Bolt\\Configuration\\' . $configType;
                $config = new $configClass($loader);
                break;
            }
        }

        // None of the mappings matched, error
        if (!isset($config)) {
            include $boltRootPath . '/src/Exception/LowlevelException.php';
            throw new LowlevelException(
                "Configuration autodetection failed because The file " .
                "<code>vendor/autoload.php</code> doesn't exist. Make sure " .
                "you've installed the required components with Composer."
            );
        }

        // Register a PHP shutdown function to catch early fatal errors
        register_shutdown_function(array('\Bolt\Exception\LowlevelException', 'catchFatalErrorsEarly'));

        /** @var \Bolt\Configuration\ResourceManager $config */
        $config->verify();
        $config->compat();

        // Create the 'Bolt application'
        $app = new Application(array('resources' => $config));

        // Register a PHP shutdown function to catch fatal errors with the application object
        register_shutdown_function(array('\Bolt\Exception\LowlevelException', 'catchFatalErrors'), $app);

        // Initialize the 'Bolt application': Set up all routes, providers, database, templating, etc..
        $app->initialize();

        return $app;
    }
);
