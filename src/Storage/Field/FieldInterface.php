<?php
namespace Bolt\Storage\Field;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;

/**
 * Interface implemented by content fields.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
interface FieldInterface
{
    /**
     * Returns the name of the field.
     *
     * @return string The field name
     */
    public function getName();

    /**
     * Returns the path to the template.
     *
     * @return string The template name
     */
    public function getTemplate();

    /**
     * Returns the storage type.
     *
     * @throws DBALException
     *
     * @return Type A Valid Storage Type
     */
    public function getStorageType();

    /**
     * Returns additional options to be passed to the storage field.
     *
     * @return array An array of options
     */
    public function getStorageOptions();
}
