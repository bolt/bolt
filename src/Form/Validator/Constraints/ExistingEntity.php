<?php

namespace Bolt\Form\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Existing entity validation constraint.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExistingEntity extends Constraint
{
    const ENTITY_EXISTS_ERROR = '63e88f63-0a35-4ac9-9d55-ad987e1d5c7b';

    protected static $errorNames = [
        self::ENTITY_EXISTS_ERROR => 'ENTITY_EXISTS_ERROR',
    ];

    public $message = 'Entity of type "%type%", with matching "%fields%" field already exist.';
    /** @var string Class name or alias that is passed to getRepository() */
    public $className;
    /** @var array Field names that are OR-ed in the SQL query */
    public $fieldNames;

    /**
     * Constructor.
     *
     * @param array|null $options
     */
    public function __construct($options = null)
    {
        if ($options !== null && !is_array($options)) {
            $options = [
                'className'  => $options,
                'fieldNames' => $options,
            ];
        }

        parent::__construct($options);

        if ($this->className === null && $this->fieldNames === null) {
            throw new MissingOptionsException(sprintf('Both option "className" and "fieldNames" must be given for constraint %s', __CLASS__), ['className', 'fieldNames']);
        }
    }
}
