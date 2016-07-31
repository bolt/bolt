<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Configuration\ResourceManager;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class HtmlType extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $key = $this->mapping['fieldname'];
        $value = $entity->get($key);

        $app = ResourceManager::getApp();
        $config = $app['config']->get('general');

        // For HTML fields we want to override a few tags. For example, it makes no sense to disallow `<embed>`, if we have `embed: true` in our config.yml.
        $allowed_because_wysiwyg = ['div', 'p', 'br', 'strong', 'em', 'i', 'b', 'li', 'ul', 'ol', 'pre', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'a'];

        if ($config['wysiwyg']['images'] === true) {
            $allowed_because_wysiwyg[] = 'img';
        }
        if ($config['wysiwyg']['tables'] === true) {
            $allowed_because_wysiwyg = array_merge($allowed_because_wysiwyg, ['table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr']);
        }
        if ($config['wysiwyg']['fontcolor'] === true) {
            $allowed_because_wysiwyg[] = 'span';
        }
        if ($config['wysiwyg']['subsuper'] === true) {
            $allowed_because_wysiwyg = array_merge($allowed_because_wysiwyg, ['sub', 'sup']);
        }
        if ($config['wysiwyg']['underline'] === true) {
            $allowed_because_wysiwyg[] = 'u';
        }
        if ($config['wysiwyg']['embed'] === true) {
            $allowed_because_wysiwyg[] = 'iframe'; // Note: Only <iframe>. Not <script>, <embed> or <object>.
        }
        if ($config['wysiwyg']['ruler'] === true) {
            $allowed_because_wysiwyg[] = 'hr';
        }
        if ($config['wysiwyg']['strike'] === true) {
            $allowed_because_wysiwyg[] = 's';
        }
        if ($config['wysiwyg']['blockquote'] === true) {
            $allowed_because_wysiwyg[] = 'blockquote';
        }
        if ($config['wysiwyg']['codesnippet'] === true) {
            $allowed_because_wysiwyg = array_merge($allowed_because_wysiwyg, ['code', 'pre', 'tt']);
        }

        $allowed_tags = array_unique(array_merge($config['htmlcleaner']['allowed_tags'], $allowed_because_wysiwyg));

        $value = parent::sanitize($value, $allowed_tags, $config['htmlcleaner']['allowed_attributes']);
        $entity->set($key, $value);

        parent::persist($queries, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'html';
    }

    /**
     * Returns the name of the Doctrine storage type to use for a field.
     *
     * @return Type
     */
    public function getStorageType()
    {
        return Type::getType('text');
    }
}
