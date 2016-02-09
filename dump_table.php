<?php

require_once getenv('HOME') . '/.composer/vendor/autoload.php';

/** @var \Silex\Application $app */
$app = require_once __DIR__ . '/app/bootstrap.php';
$app['pimpledump.output_dir'] = __DIR__;

$pdp = new Sorien\Provider\PimpleDumpProvider();
$app->register($pdp);
$app->boot();
$dump = $pdp->getArray($app);

// dump($dump);

echo "<table>";

foreach($dump as $item) {
    echo "<tr>";
    echo "<td>" . $item['name'] . "</td>";
    echo "<td>" . $item['type'] . "</td>";
    if (is_array($item['value'])) {
        echo "<td><em>See children, below</em></td>";
    } else {
        echo "<td>" . $item['value'] . "</td>";
    }
    echo "<td>" . $item['file'] . "</td>";
    echo "</tr>";

    if (is_array($item['value'])) {
        foreach($item['value'] as $subitem) {
            echo "<tr>";
            echo "<td>" . $item['name'] . " -> " . $subitem['name'] . "</td>";
            echo "<td>" . $subitem['type'] . "</td>";
            echo "<td>" . $subitem['value'] . "</td>";
            echo "<td>" . $subitem['file'] . "</td>";
            echo "</tr>";
        }
    }

}