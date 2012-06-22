<?php

$config = array();

// Read the config
$yamlparser = new Symfony\Component\Yaml\Parser();
$config['general'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/config.yml'));
$config['taxonomy'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/taxonomy.yml'));
$config['contenttypes'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/contenttypes.yml'));

$configdb = $config['general']['database'];

if (isset($configdb['driver']) && ($configdb['driver'] == "pdo_sqlite") ) {
    
    $basename = basename($configdb['database']);
    if (getExtension($basename)!="db") { $basename .= ".db"; };
    
    $dboptions = array(
        'driver' => 'pdo_sqlite',
        'dbname' => __DIR__ . "/" . $basename
    );
    
} else {
    // Assume we configured it correctly. Yeehaa!
    $dboptions = array(
        'driver'    => (isset($configdb['driver']) ? $configdb['driver'] : 'pdo_mysql'),
        'host'      => (isset($configdb['host']) ? $configdb['host'] : 'localhost'),
        'dbname'    => $configdb['databasename'],
        'user'      => $configdb['username'],
        'password'  => $configdb['password'],
        'port'      => (isset($configdb['host']) ? $configdb['host'] : '3306'),
    );
    
}

// I don't think i can set Twig's path in runtime, so we have to resort to hackishness to set the 
// path.. if the request URI starts with '/pilex' in the URL, we assume we're in the Backend.. Yeah..
if (strpos($_SERVER['REQUEST_URI'], "/pilex") === 0) {
    $config['twigpath'] = __DIR__.'/view';
} else {
    $config['twigpath'] = array(__DIR__.'/../view', __DIR__.'/view');
}