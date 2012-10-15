#!/usr/bin/php
<?php
require dirname(__FILE__) .'/markdownify_extra.php';

function param($name, $default = false) {
  if (!in_array('--'.$name, $_SERVER['argv']))
    return $default;
  reset($_SERVER['argv']);
  while (each($_SERVER['argv'])) {
    if (current($_SERVER['argv']) == '--'.$name)
      break;
  }
  $value = next($_SERVER['argv']);
  if ($value === false || substr($value, 0, 2) == '--')
    return true;
  else
    return $value;
}


$input = stream_get_contents(STDIN);

$linksAfterEachParagraph = param('links');
$bodyWidth = param('width');
$keepHTML = param('html', true);

if (param('no_extra')) {
  $parser = new Markdownify($linksAfterEachParagraph, $bodyWidth, $keepHTML);
} else {
  $parser = new Markdownify_Extra($linksAfterEachParagraph, $bodyWidth, $keepHTML);
}

echo $parser->parseString($input) ."\n";