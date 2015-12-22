<?php

namespace Bolt\Composer\EventListener;

use JsonSerializable;

/**
 * Package reference descriptor.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class PackageDescriptor implements JsonSerializable
{
    protected $name;
    protected $class;
    protected $path;
    protected $constraint;
    protected $valid = true;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $class
     * @param string $path
     * @param string $constraint
     * @param bool   $valid
     */
    public function __construct($name, $class, $path, $constraint, $valid = true)
    {
        $this->name = $name;
        $this->class = $class;
        $this->path = $path;
        $this->constraint = $constraint;
        $this->valid = $valid;
    }

    /**
     * Creating calss from unknown JSON data.
     *
     * @param       $path
     * @param array $jsonData
     *
     * @return PackageDescriptor
     */
    public static function parse($path, array $jsonData)
    {
        $name = $jsonData['name'];
        $class = self::setClass($jsonData);
        $constraint = self::setConstraint($jsonData);
        $valid = $class && $constraint;

        return new self($name, $class, $path, $constraint, $valid);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return [
            'name'       => $this->name,
            'path'       => $this->path,
            'class'      => $this->class,
            'constraint' => $this->constraint,
            'valid'      => $this->valid,
        ];
    }

    /**
     * Record the package's loading class.
     *
     * @param array $jsonData
     *
     * @return string|null
     */
    private static function setClass(array $jsonData)
    {
        if (isset($jsonData['extra']['bolt-class'])) {
            return $jsonData['extra']['bolt-class'];
        }
    }

    /**
     * Record the package's version constraints.
     *
     * @param array $jsonData
     *
     * @return string|null
     */
    private static function setConstraint(array $jsonData)
    {
        if (isset($jsonData['require']['bolt/bolt'])) {
            return $jsonData['require']['bolt/bolt'];
        }
    }
}
