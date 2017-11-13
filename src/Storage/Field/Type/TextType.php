<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\Field\Sanitiser\SanitiserAwareInterface;
use Bolt\Storage\Field\Sanitiser\SanitiserAwareTrait;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * Note: The persist() override was removed, because it was sanitising fields
 * when not desired. See https://github.com/bolt/bolt/issues/5789 for details.
 *
 * Note: After the removal of the persist() method, we can use the
 * SanitiserAwareTrait again, ensuring editors don't inadvertently insert
 * javascript in `type: text` fields. Hopefully this will also help a bit in
 * the never-ending "OMFG, an editor can self-XSS!!1!one!" discussions.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TextType extends FieldTypeBase implements SanitiserAwareInterface
{
    use SanitiserAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'text';
    }
}
