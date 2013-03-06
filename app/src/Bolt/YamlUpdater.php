<?php

namespace Bolt;

/**
 * Allows (simple) modifications of Bolt .yml files.
 *
 * @author Bob den Otter, bob@twokings.nl
 *
 **/
class YamlUpdater
{

    private $changed = false;

    public function __construct($filename = "")
    {

    }

    public function change($key, $value, $near="") {

    }

    /**
     * Verify if the modified yaml is still a valid .yml file, and if we
     * are actually allowed to write and update the current file.
     */
    public function verify()
    {

    }

    /**
     * Save our modified .yml file.
     * @param bool $makebackup
     */
    public function save($makebackup = true)
    {

    }


}
