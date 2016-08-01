<?php

namespace Bolt\Composer\Package;

use JsonSerializable;

/**
 * Composer package dependency relationship.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DependencyRelationship implements JsonSerializable
{
    /** @var string */
    protected $sourceName;
    /** @var string */
    protected $sourceVersion;
    /** @var string */
    protected $targetName;
    /** @var string */
    protected $targetVersion;
    /** @var string */
    protected $reason;

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize()
    {
        return $this->serialize();
    }

    /**
     * Return object as array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->serialize();
    }

    /**
     * @return string
     */
    public function getSourceName()
    {
        return $this->sourceName;
    }

    /**
     * @param string $sourceName
     *
     * @return DependencyRelationship
     */
    public function setSourceName($sourceName)
    {
        $this->sourceName = $sourceName;

        return $this;
    }

    /**
     * @return string
     */
    public function getSourceVersion()
    {
        return $this->sourceVersion;
    }

    /**
     * @param string $sourceVersion
     *
     * @return DependencyRelationship
     */
    public function setSourceVersion($sourceVersion)
    {
        $this->sourceVersion = $sourceVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargetName()
    {
        return $this->targetName;
    }

    /**
     * @param string $targetName
     *
     * @return DependencyRelationship
     */
    public function setTargetName($targetName)
    {
        $this->targetName = $targetName;

        return $this;
    }

    /**
     * @return string
     */
    public function getTargetVersion()
    {
        return $this->targetVersion;
    }

    /**
     * @param string $targetVersion
     *
     * @return DependencyRelationship
     */
    public function setTargetVersion($targetVersion)
    {
        $this->targetVersion = $targetVersion;

        return $this;
    }

    /**
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     *
     * @return DependencyRelationship
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return array
     */
    private function serialize()
    {
        return [
            'source_name'    => $this->sourceName,
            'source_version' => $this->sourceVersion,
            'target_name'    => $this->targetName,
            'target_version' => $this->targetVersion,
            'reason'         => $this->reason,
        ];
    }
}
