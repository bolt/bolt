<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

if ($uri !== '/' and file_exists(__DIR__ . '/' .$uri))
{
	return false;
}

require_once __DIR__ . '/index.php';
