<?php

namespace Bolt\Storage\Field;

use Doctrine\DBAL\Types\Type;

class Base implements FieldInterface
{
    /** @var string */
    public $name;
    /** @var string */
    public $template;

    /**
     * Constructor.
     *
     * @param string $name
     * @param string $template
     */
    public function __construct($name, $template)
    {
        $this->name = $name;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {
        return Type::getType('text');
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageOptions()
    {
        return [];
    }
}
