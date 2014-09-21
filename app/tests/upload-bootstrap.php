<?php

namespace Sirius\Upload\Container;

/**
 * This is a bit of a hack to overload the native functions for uploads
 *
 * Because is_uploaded_file and move_uploaded_file actually ensure the files were sent
 * via a POST request, we don't have the ability to spoof this in unit tests.
 *
 * So these will perform the same function without failing if the files are simple arrays
 *
 *
 **/


function is_uploaded_file($file)
{
    return file_exists($file);
}

function move_uploaded_file($source, $destination)
{
    return copy($source, $destination);
}
