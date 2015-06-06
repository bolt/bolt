<?php
namespace Bolt\Database\Table;

use Doctrine\DBAL\Schema\Table;

class ContentType extends BaseTable
{
    /**
     * {@inheritdoc}
     */
    protected function addColumns()
    {
        // @codingStandardsIgnoreStart
        $this->table->addColumn('id',             'integer',  ['autoincrement' => true]);
        $this->table->addColumn('slug',           'string',   ['length' => 128]);
        $this->table->addColumn('datecreated',    'datetime', []);
        $this->table->addColumn('datechanged',    'datetime', []);
        $this->table->addColumn('datepublish',    'datetime', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('datedepublish',  'datetime', ['notnull' => false, 'default' => null]);
        $this->table->addColumn('templatefields', 'text',     ['default' => '']);
        $this->table->addColumn('username',       'string',   ['length' => 32, 'default' => '', 'notnull' => false]); // We need to keep this around for backward compatibility. For now.
        $this->table->addColumn('ownerid',        'integer',  ['notnull' => false]);
        $this->table->addColumn('status',         'string',   ['length' => 32]);
        // @codingStandardsIgnoreEnd
    }

    /**
     * {@inheritdoc}
     */
    protected function addIndexes()
    {
        $this->table->addIndex(['slug']);
        $this->table->addIndex(['datecreated']);
        $this->table->addIndex(['datechanged']);
        $this->table->addIndex(['datepublish']);
        $this->table->addIndex(['datedepublish']);
        $this->table->addIndex(['status']);
    }

    /**
     * {@inheritdoc}
     */
    protected function setPrimaryKey()
    {
        $this->table->setPrimaryKey(['id']);
    }

    /**
     * Add the contenttype's specific fields.
     *
     * @param \Doctrine\DBAL\Schema\Table $table
     * @param string                      $fieldName
     * @param string                      $type
     */
    public function addCustomFields(Table $table, $fieldName, $type)
    {
        switch ($type) {
            case 'text':
            case 'templateselect':
            case 'file':
                $table->addColumn($fieldName, 'string', ['length' => 256, 'default' => '']);
                break;
            case 'float':
                $table->addColumn($fieldName, 'float', ['default' => 0]);
                break;
            case 'number': // deprecated.
                $table->addColumn($fieldName, 'decimal', ['precision' => '18', 'scale' => '9', 'default' => 0]);
                break;
            case 'integer':
                $table->addColumn($fieldName, 'integer', ['default' => 0]);
                break;
            case 'checkbox':
                $table->addColumn($fieldName, 'boolean', ['default' => 0]);
                break;
            case 'html':
            case 'textarea':
            case 'image':
            case 'video':
            case 'markdown':
            case 'geolocation':
            case 'filelist':
            case 'imagelist':
            case 'select':
                $table->addColumn($fieldName, 'text', ['default' => $this->getTextDefault()]);
                break;
            case 'datetime':
                $table->addColumn($fieldName, 'datetime', ['notnull' => false]);
                break;
            case 'date':
                $table->addColumn($fieldName, 'date', ['notnull' => false]);
                break;
            case 'slug':
                // Only additional slug fields will be added. If it's the
                // default slug, skip it instead.
                if ($fieldName != 'slug') {
                    $table->addColumn($fieldName, 'string', ['length' => 128, 'notnull' => false, 'default' => '']);
                }
                break;
            case 'id':
            case 'datecreated':
            case 'datechanged':
            case 'datepublish':
            case 'datedepublish':
            case 'username':
            case 'status':
            case 'ownerid':
                // These are the default columns. Don't try to add these.
                break;
            default:
                if ($handler = $this->app['config']->getFields()->getField($type)) {
                    /** @var $handler \Bolt\Field\FieldInterface */
                    $table->addColumn($fieldName, $handler->getStorageType(), $handler->getStorageOptions());
                }
        }
    }
}
