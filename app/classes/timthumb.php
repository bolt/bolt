<?php
/**
 * TimThumb by Ben Gillbanks and Mark Maunder
 * Based on work done by Tim McDaniels and Darren Hoyt
 * http://code.google.com/p/timthumb/
 *
 * GNU General Public License, version 2
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 *
 * Examples and documentation available on the project homepage
 * http://www.binarymoon.co.uk/projects/timthumb/
 *
 * $Rev$
 */

/**
 * Bolt specific code: turn /100x200f/foo.jpg into $_GET parameters timthumb understands.
 *
 * options for zoomcrop:
 * http://www.binarymoon.co.uk/demo/timthumb-zoom-crop/
 * 'f' ('fit') -> zc=0
 * 'c' (crop, default) -> zc=1
 * 'b' (borders) -> zc=2
 * 'r' (resize) -> zc=3
 */

// echo "<pre>\n" . print_r($_SERVER, true) . "</pre>\n";

$requesturi = $_SERVER['REQUEST_URI'];

$res = preg_match("^thumbs/([0-9]+)x([0-9]+)([a-z]?)/(.*)^i", $requesturi , $matches);

if (empty($matches[1]) || empty($matches[2]) || empty($matches[4])) {
    //die("Malformed thumbnail URL. Should look like '/thumbs/320x240c/filename.jpg'.");
}

// Bolt specific: Set LOCAL_FILE_BASE_DIRECTORY, used below..
define('LOCAL_FILE_BASE_DIRECTORY', dirname(dirname(dirname(__FILE__))));

$_GET['src'] = "files/".urldecode($matches[4]);
$_GET['src'] = str_replace("files/files/", "files/", $_GET['src']);

$_GET['w'] = $matches[1];
$_GET['h'] = $matches[2];

switch ($matches[3]) {
    case "f":
        $_GET['zc'] = 0;
        break;
    case "b":
        $_GET['zc'] = 2;
        break;
    case "r":
        $_GET['zc'] = 3;
        break;
    case "c":
    default:
        $_GET['zc'] = 1;
        break;
}



// Implode back to _SERVER['QUERY_STRING'], because that's used for the cache filename generation, around line 313 or so
$_SERVER['QUERY_STRING'] = http_build_query($_GET);


require_once(__DIR__.'/timthumb-config.php');
require_once(__DIR__.'/../../vendor/taha/timthumb/timthumb.php');
timthumb::start();
