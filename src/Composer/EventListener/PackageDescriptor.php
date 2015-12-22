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
     * @param       $path
     * @param array $jsonData
     */
    public function __construct($path, array $jsonData)
    {
        $this->path = $path;
        $this->name = $jsonData['name'];
        $this->setClass($jsonData);
        $this->setConstraint($jsonData);
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
     */
    private function setClass(array $jsonData)
    {
        if (isset($jsonData['extra']['bolt-class'])) {
            $this->class = $jsonData['extra']['bolt-class'];
        } else {
            $this->valid = false;
        }
    }

    /**
     * Record the package's version constraints.
     *
     * @param array $jsonData
     */
    private function setConstraint(array $jsonData)
    {
        if (isset($jsonData['require']['bolt/bolt'])) {
            $this->constraint = $jsonData['require']['bolt/bolt'];
        } else {
            $this->valid = false;
        }
    }
}
