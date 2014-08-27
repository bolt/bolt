<?php
namespace Bolt\Field;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class Unknown implements FieldInterface
{

    public $name;

    public $template;

    protected $storageType;

    protected $storageOptions;

    public function __construct($name)
    {
        $this->name = $name;
        $this->template = 'editcontent/fields/_unknown.twig';
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTemplate()
    {
        return $this->template;
    }

    public function getStorageType()
    {
        return $this->storageType;
    }

    public function getStorageOptions()
    {
        return $this->storageOptions;
    }

    public function setByColumn(Column $column)
    {
        $this->name = $column->getName();
        $this->storageType = $this->findStorageType(get_class($column->getType()));
    }

    public function setStorageOptions($options)
    {
        $this->storageOptions = $options;
    }

    protected function findStorageType($dbaltype)
    {
        $types = array_combine(array_values(Type::getTypesMap()), array_keys(Type::getTypesMap()));
        return $types[$dbaltype];
    }
}
