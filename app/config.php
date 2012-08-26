<?php

$config = array();

// Read the config
$yamlparser = new Symfony\Component\Yaml\Parser();
$config['general'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/config.yml'));
$config['taxonomy'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/taxonomy.yml'));
$config['contenttypes'] = $yamlparser->parse(file_get_contents(__DIR__.'/config/contenttypes.yml'));

// Assume some sensible defaults for some options
$defaultconfig = array(
    'sitename' => 'Default Pilex site',
    'homepage' => 'page/*',
    'homepage_template' => 'index.twig',
    'contentperpage' => 10,
    'contentperdashboardwidget' => 5,
    'debug' => false,
    'strict_variables' => false
);
$config['general'] = array_merge($defaultconfig, $config['general']);


// Clean up taxonomies
foreach( $config['taxonomy'] as $key => $value ) {
    if (!isset($config['taxonomy'][$key]['name'])) {
        $config['taxonomy'][$key]['name'] = ucwords($config['taxonomy'][$key]['slug']);
    }
    if (!isset($config['taxonomy'][$key]['singular_name'])) {
        $config['taxonomy'][$key]['singular_name'] = ucwords($config['taxonomy'][$key]['singular_slug']);
    }
}

// Clean up taxonomies


// echo "<pre>\n" . util::var_dump($config['general'], true) . "</pre>\n";

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
        'port'      => (isset($configdb['port']) ? $configdb['port'] : '3306'),
    );
    
}

// I don't think i can set Twig's path in runtime, so we have to resort to hackishness to set the 
// path.. if the request URI starts with '/pilex' in the URL, we assume we're in the Backend.. Yeah..
if (strpos($_SERVER['REQUEST_URI'], "/pilex") === 0) {
    $config['twigpath'] = __DIR__.'/view';
} else {
    $config['twigpath'] = array(__DIR__.'/../view', __DIR__.'/view');
}