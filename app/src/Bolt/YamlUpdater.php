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
    private $pointer = 0;
    private $lines = 0;
    private $file = array();
    private $filename;

    public function __construct($filename = "")
    {
        if (!is_readable($filename)) {
            echo "can't read $filename\n";
            return false;
        }

        $this->filename = $filename;
        $this->file = file($filename);
        $this->lines = count($this->file);

        $this->changed = false;

    }

    /**
     * Get a value from the yml. return an array with info
     *
     * @param string $key
     * @return bool|array
     */
    public function get($key) {

        $this->pointer = 0;
        $result = false;
        $keyparts = explode("/", $key);

        foreach($keyparts as $count => $keypart) {
            $result = $this->find($keypart, $count);
        }

        if ($result !== false) {
            return $this->parseline($result);
        } else {
            return false;
        }


    }

    /**
     * Find a specific part of the key, starting from $this->pointer
     *
     * @param $keypart
     * @param int $indent
     * @return bool|int
     */
    private function find($keypart, $indent = 0)
    {
        // Pointer is past end of file..
        if ($this->pointer > $this->lines) {
            return false;
        }

        $needle = substr("                                      ", 0, 2*$indent) . $keypart.":";

        if (strpos($this->file[$this->pointer], $needle)===0) {
            return $this->pointer;
        } else {
            $this->pointer++;
            return $this->find($keypart, $indent);
        }
    }

    /**
     * Parse a specific line-number into its key, value parts, with the used indentation.
     * @param $line
     * @return array
     */
    private function parseline($line)
    {

        preg_match_all('/(\s*)([a-z0-9_-]+):(\s)?(.*)/', $this->file[$line], $match);

        return array(
            'line' => $line,
            'indentation' => $match[1][0],
            'key' => $match[2][0],
            'value' => $match[4][0]
        );

    }

    /**
     * Change a key into a new value. Save .yml afterwards.
     *
     * @param $key
     * @param $value
     * @return bool
     */
    public function change($key, $value)
    {

        $match = $this->get($key);

        // Not found.
        if (!$match) {
            return false;
        }

        $value = $this->prepareValue($value);

        $this->file[$match['line']] = sprintf("%s%s: %s\n", $match['indentation'], $match['key'], $value);

        // print_r($match);

        return $this->save();

    }


    /**
     * Make sure the value is escaped as a yaml value..
     *
     * array('one', 'two', 'three') => [ one, two, three ]
     * "usin' quotes" => 'usin'' quotes
     *
     * @param string $value
     * @return string
     */
    public function prepareValue($value) {

        if (is_array($value)) {
            return "[ " . implode(", ", $value) . " ]";
        }

        if (preg_match('/[^a-z0-9]/i', $value)) {
            return "'" . str_replace("'", "''", $value) . "'";
        }

        return $value;

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
        if($makebackup) {
            // TODO: make a backup..
        }

        $tmpfile = $this->filename.".tmp";
        file_put_contents($tmpfile, implode("", $this->file));

        if (is_readable($tmpfile) && is_writable($this->filename)) {
            rename($tmpfile, $this->filename);
            return true;
        } else {
            return false;
        }

    }


}
