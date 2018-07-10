<?php

namespace Bolt\Storage\Entity;

use Bolt\Common\Json;
use Bolt\Helpers\Input;
use Bolt\Legacy;
use Bolt\Library as Lib;
use Bolt\Storage\Field\Collection\RepeatingFieldCollection;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Markup;

/**
 * Trait class for ContentType relations.
 *
 * This is a breakout of the old Bolt\Content class and serves two main purposes:
 *   * Maintain backward compatibility for Bolt\Content through the remainder of
 *     the 2.x development/release life-cycle
 *   * Attempt to break up former functionality into sections of code that more
 *     resembles Single Responsibility Principles
 *
 * These traits should be considered transitional, the functionality in the
 * process of refactor, and not representative of a valid approach.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentValuesTrait
{
    /** @var bool Whether this is a "real" ContentType or an embedded ones */
    protected $isRootType;

    /**
     * Pseudo-magic function, used for when templates use {{ content.get(title) }},
     * so we can map it to $this->values['title'].
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        // For fields that are stored as arrays, like 'video'
        if (strpos($name, '.') > 0) {
            list($name, $attr) = explode('.', $name);
            if (!empty($attr) && isset($this->values[$name][$attr])) {
                return $this->values[$name][$attr];
            }
        }

        if (isset($this->values[$name])) {
            return $this->values[$name];
        }

        return false;
    }

    /**
     * Return a content objects values.
     *
     * @param bool $json     Set to TRUE to return JSON encoded values for arrays
     * @param bool $stripped Set to true to strip all of the base fields
     *
     * @return array
     */
    public function getValues($json = false, $stripped = false)
    {
        // Prevent 'slug may not be NULL'
        if (!isset($this->values['slug'])) {
            $this->values['slug'] = '';
        }

        // Return raw values
        if ($json === false) {
            return $this->values;
        }

        $contenttype = $this->contenttype;
        if (!$stripped) {
            $newvalue = $this->values;
        } else {
            $newvalue = [];
        }

        // add the fields for this contenttype,
        if (is_array($contenttype)) {
            foreach ($contenttype['fields'] as $field => $property) {
                if (!isset($this->values[$field])) {
                    continue;
                }
                switch ($property['type']) {
                    // Set the slug, while we're at it
                    case 'slug':
                        if (!empty($property['uses']) && empty($this->values[$field])) {
                            $uses = '';
                            foreach ($property['uses'] as $usesField) {
                                $uses .= $this->values[$usesField] . ' ';
                            }
                            $newvalue[$field] = $this->app['slugify']->slugify($uses);
                        } elseif (!empty($this->values[$field])) {
                            $newvalue[$field] = $this->app['slugify']->slugify($this->values[$field]);
                        } elseif (empty($this->values[$field]) && $this->values['id']) {
                            $newvalue[$field] = $this->values['id'];
                        }
                        break;

                    case 'video':
                        foreach (['html', 'responsive'] as $subkey) {
                            if (!empty($this->values[$field][$subkey])) {
                                $this->values[$field][$subkey] = (string) $this->values[$field][$subkey];
                            }
                        }
                        if (!empty($this->values[$field]['url'])) {
                            $newvalue[$field] = Json::dump($this->values[$field]);
                        } else {
                            $newvalue[$field] = '';
                        }
                        break;

                    case 'geolocation':
                        if (!empty($this->values[$field]['latitude']) && !empty($this->values[$field]['longitude'])) {
                            $newvalue[$field] = Json::dump($this->values[$field]);
                        } else {
                            $newvalue[$field] = '';
                        }
                        break;

                    case 'image':
                        if (!empty($this->values[$field]['file'])) {
                            $newvalue[$field] = Json::dump($this->values[$field]);
                        } else {
                            $newvalue[$field] = '';
                        }
                        break;

                    case 'imagelist':
                    case 'filelist':
                        if (is_array($this->values[$field])) {
                            $newvalue[$field] = Json::dump($this->values[$field]);
                        } elseif (!empty($this->values[$field]) && strlen($this->values[$field]) < 3) {
                            // Don't store '[]'
                            $newvalue[$field] = '';
                        }
                        break;

                    case 'integer':
                        $newvalue[$field] = round($this->values[$field]);
                        break;

                    case 'embed':
                    case 'select':
                        if (is_array($this->values[$field])) {
                            $newvalue[$field] = Json::dump($this->values[$field]);
                        } else {
                            $newvalue[$field] = $this->values[$field];
                        }
                        break;

                    case 'html':
                        // Remove &nbsp; characters from CKEditor, unless configured to leave them in.
                        if (!$this->app['config']->get('general/wysiwyg/ck/allowNbsp')) {
                            $newvalue[$field] = str_replace('&nbsp;', ' ', $this->values[$field]);
                        }
                        break;
                    default:
                        $newvalue[$field] = $this->values[$field];
                        break;
                }
            }
        }

        if (!$stripped) {
            if (!empty($this['templatefields'])) {
                $newvalue['templatefields'] = Json::dump($this->values['templatefields']->getValues(true, true));
            } else {
                $newvalue['templatefields'] = '';
            }
        }

        return $newvalue;
    }

    /**
     * Set a ContentType record's individual value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setValue($key, $value)
    {
        // Don't set templateFields if not a real contenttype
        if (($key === 'templatefields') && (!$this->isRootType)) {
            return;
        }

        // Check if the value need to be unserialized.
        if (is_string($value) && substr($value, 0, 2) === 'a:') {
            try {
                $unserdata = Lib::smartUnserialize($value);
            } catch (\Exception $e) {
                $unserdata = false;
            }

            if ($unserdata !== false) {
                $value = $unserdata;
            }
        }

        if ($key === 'id') {
            $this->id = $value;
        }

        // Set the user in the object.
        if ($key === 'ownerid' && !empty($value)) {
            $this->user = $this->app['users']->getUser($value);
        }

        // Only set values if they have are actually a field.
        $allowedcolumns = self::getBaseColumns();
        $allowedcolumns[] = 'taxonomy';
        if (!isset($this->contenttype['fields'][$key]) && !in_array($key, $allowedcolumns)) {
            return;
        }

        /*
         * This Block starts introducing new-style hydration into the legacy content object.
         * To do this we fetch the new field from the manager and hydrate a temporary entity.
         *
         * We don't return at this point so continue to let other transforms happen below so the
         * old behaviour will still happen where adjusted.
         */
        if (isset($this->contenttype['fields'][$key]['type']) && $this->app['storage.field_manager']->hasCustomHandler($this->contenttype['fields'][$key]['type'])) {
            $newFieldType = $this->app['storage.field_manager']->getFieldFor($this->contenttype['fields'][$key]['type']);
            $newFieldType->mapping['fieldname'] = $key;
            $entity = new Content();
            // Note: Extensions _should_ implement \Bolt\Storage\Field\Type\FieldTypeInterface,
            // but if they don't, 'hydrate' doesn't exist.
            if (method_exists($newFieldType, 'hydrate')) {
                $newFieldType->hydrate([$key => $value], $entity);
            }
            $value = $entity->$key;
        }

        if (in_array($key, ['datecreated', 'datechanged', 'datepublish', 'datedepublish'])) {
            if (!preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $value)) {
                // @todo Try better date-parsing, instead of just setting it to
                // 'now' (or 'the past' for datedepublish)
                if ($key === 'datedepublish') {
                    $value = null;
                } else {
                    $value = date('Y-m-d H:i:s');
                }
            }
        }

        if ($key === 'templatefields') {
            if ((is_string($value)) || (is_array($value))) {
                if (is_string($value)) {
                    try {
                        $unserdata = Lib::smartUnserialize($value);
                    } catch (\Exception $e) {
                        $unserdata = false;
                    }
                } else {
                    $unserdata = $value;
                }

                if (is_array($unserdata)) {
                    if (is_a($this, Legacy\Content::class)) {
                        $value = new static($this->app, $this->getTemplateFieldsContentType(), [], false);
                    } else {
                        $value = new Legacy\Content($this->app, $this->getTemplateFieldsContentType(), [], false);
                    }
                    $value->setValues($unserdata);
                } else {
                    $value = null;
                }
            }
        }

        if (!isset($this->values['datechanged']) ||
            !preg_match("/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/", $this->values['datechanged'])) {
            $this->values['datechanged'] = date('Y-m-d H:i:s');
        }

        $this->values[$key] = $value;
    }

    /**
     * Set a ContentType record's values.
     *
     * @param array $values
     */
    public function setValues(array $values)
    {
        // Since Bolt 1.4, we use 'ownerid' instead of 'username' in the DB tables. If we get an array that has an
        // empty 'ownerid', attempt to set it from the 'username'. In $this->setValue the user will be set, regardless
        // of ownerid is an 'id' or a 'username'.
        if (empty($values['ownerid']) && !empty($values['username'])) {
            $values['ownerid'] = $values['username'];
            unset($values['username']);
        }

        foreach ($values as $key => $value) {
            if ($key !== 'templatefields') {
                $this->setValue($key, $value);
            }
        }

        // If default status is set in contentttype.
        if (empty($this->values['status']) && isset($this->contenttype['default_status'])) {
            $this->values['status'] = $this->contenttype['default_status'];
        }

        $serializedFieldTypes = [
            'embed',
            'geolocation',
            'imagelist',
            'image',
            'file',
            'filelist',
            'video',
            'select',
            'templateselect',
            'checkbox',
            'repeater',
        ];
        // Check if the values need to be unserialized, and pre-processed.
        foreach ($this->values as $key => $value) {
            if ((in_array($this->fieldType($key), $serializedFieldTypes)) || ($key === 'templatefields')) {
                if (!empty($value) && is_string($value) && (substr($value, 0, 2) === 'a:' || $value[0] === '[' || $value[0] === '{')) {
                    try {
                        $unserdata = Lib::smartUnserialize($value);
                    } catch (\Exception $e) {
                        $unserdata = false;
                    }

                    if ($unserdata !== false) {
                        $this->values[$key] = $unserdata;
                    }
                }
            }

            if ($this->fieldType($key) === 'video' && is_array($this->values[$key]) && !empty($this->values[$key]['url'])) {
                $defaultValues = [
                    'html'       => '',
                    'responsive' => '',
                    'width'      => '1',
                    'height'     => '1',
                    'ratio'      => '1',
                ];

                $video = array_replace($defaultValues, $this->values[$key]);

                // update the HTML, according to given width and height
                if (!empty($video['width']) && !empty($video['height'])) {
                    $video['html'] = preg_replace("/width=(['\"])([0-9]+)(['\"])/i", 'width=${1}' . $video['width'] . '${3}', $video['html']);
                    $video['html'] = preg_replace("/height=(['\"])([0-9]+)(['\"])/i", 'height=${1}' . $video['height'] . '${3}', $video['html']);
                }

                $responsiveclass = 'responsive-video';

                // See if it's widescreen or not.
                if (!empty($video['height']) && (((int) $video['width'] / (int) $video['height']) > 1.76)) {
                    $responsiveclass .= ' widescreen';
                }

                if (strpos($video['url'], 'vimeo') !== false) {
                    $responsiveclass .= ' vimeo';
                }

                $video['responsive'] = sprintf('<div class="%s">%s</div>', $responsiveclass, $video['html']);

                // Mark them up as Twig markup.
                $video['html'] = new Markup($video['html'], 'UTF-8');
                $video['responsive'] = new Markup($video['responsive'], 'UTF-8');

                $this->values[$key] = $video;
            }

            if ($this->fieldType($key) === 'repeater' && is_array($this->values[$key]) && !$this->isRootType) {
                $originalMapping = null;
                $originalMapping[$key]['fields'] = $this->contenttype['fields'][$key]['fields'];
                $originalMapping[$key]['type'] = 'repeater';

                $mapping = $this->app['storage.metadata']->getRepeaterMapping($originalMapping);
                $repeater = new RepeatingFieldCollection($this->app['storage'], $mapping);
                $repeater->setName($key);

                foreach ($this->values[$key] as $subValue) {
                    $repeater->addFromArray($subValue);
                }

                $this->values[$key] = $repeater;
            }

            if ($this->fieldType($key) === 'date' || $this->fieldType($key) === 'datetime') {
                if ($this->values[$key] === '') {
                    $this->values[$key] = null;
                }
            }
        }

        // Template fields need to be done last
        // As the template has to have been selected
        if ($this->isRootType) {
            if (empty($values['templatefields'])) {
                $this->setValue('templatefields', []);
            } else {
                $this->setValue('templatefields', $values['templatefields']);
            }
        }
    }

    /**
     * Set a ContentType record values from a HTTP POST.
     *
     * @param array $values
     * @param array $contenttype
     *
     * @throws \Exception
     */
    public function setFromPost($values, $contenttype)
    {
        $values = Input::cleanPostedData($values);
        $values += ['status' => 'draft'];

        if (!$this->id) {
            // this is a new record: current user becomes the owner.
            $user = $this->app['users']->getCurrentUser();
            $this['ownerid'] = $user['id'];
        }

        // If the owner is set explicitly, check if the current user is allowed
        // to do this.
        if (isset($values['ownerid'])) {
            if ($this['ownerid'] != $values['ownerid']) {
                if (!$this->app['users']->isAllowed("contenttype:{$contenttype['slug']}:change-ownership:{$this->id}")) {
                    throw new \Exception('Changing ownership is not allowed.');
                }
                $this['ownerid'] = (int) ($values['ownerid']);
            }
        }

        // Make sure we have a proper status.
        if (!isset($values['status']) || (isset($values['status']) && !in_array($values['status'], ['published', 'timed', 'held', 'draft']))) {
            if ($this['status']) {
                $values['status'] = $this['status'];
            } else {
                $values['status'] = 'draft';
            }
        }

        // Make sure we only get the current taxonomies, not those that were fetched from the DB.
        $this->taxonomy = [];

        if (!empty($values['taxonomy'])) {
            foreach ($values['taxonomy'] as $taxonomytype => $value) {
                if (isset($values['taxonomy-order'][$taxonomytype])) {
                    foreach ($value as $k => $v) {
                        $value[$k] = $v . '#' . $values['taxonomy-order'][$taxonomytype];
                    }
                }

                $taxonomyOptions = $this->app['config']->get('taxonomy/' . $taxonomytype . '/options');

                if ($taxonomyOptions && is_array($value)) {
                    foreach ($value as $k => $v) {
                        if (isset($taxonomyOptions[$v])) {
                            $this->setTaxonomy($taxonomytype, $v, $taxonomyOptions[$v], $k);
                        }
                    }
                } elseif ($taxonomyOptions && isset($taxonomyOptions[$value])) {
                    $this->setTaxonomy($taxonomytype, $value, $taxonomyOptions[$value], 0);
                } else {
                    $this->setTaxonomy($taxonomytype, $value, $value, 0);
                }
            }

            unset($values['taxonomy']);
            unset($values['taxonomy-order']);
        }

        // Get the relations from the POST-ed values.
        if (!empty($values['relation']) && is_array($values['relation'])) {
            foreach ($values['relation'] as $key => $relationValues) {
                $this->clearRelation($key);
                foreach ($relationValues as $value) {
                    $this->setRelation($key, $value);
                }
            }
            unset($values['relation']);
        } else {
            $this->relation = [];
        }

        $this->setValues($values);
    }

    /**
     * Get the first image in the content.
     *
     * @return string
     */
    public function getImage()
    {
        // No fields, no image.
        if (empty($this->contenttype['fields'])) {
            return '';
        }

        // Grab the first field of type 'image', and return that.
        foreach ($this->contenttype['fields'] as $key => $field) {
            if ($field['type'] === 'image' && isset($this->values[$key])) {
                // After v1.5.1 we store image data as an array
                if (is_array($this->values[$key])) {
                    return isset($this->values[$key]['file']) ? $this->values[$key]['file'] : '';
                }

                return $this->values[$key];
            }
        }

        // otherwise, no image.
        return '';
    }

    /**
     * Get the title, name, caption or subject.
     *
     * @param bool $allowBasicTags
     *
     * @return string
     */
    public function getTitle($allowBasicTags = false)
    {
        $titleParts = [];

        if ($allowBasicTags === true) {
            $allowedTags = '<b><del><em><i><strong><s>';
        } else {
            $allowedTags = '';
        }

        foreach ($this->getTitleColumnName() as $fieldName) {
            // Make sure we add strings only, as some fields may be an array or DateTime.
            $value = is_array($this->values[$fieldName]) ? implode(' ', $this->values[$fieldName]) : (string) $this->values[$fieldName];
            if (strip_tags($value, $allowedTags) !== '') {
                $titleParts[] = strip_tags($value, $allowedTags);
            }
        }

        if (!empty($titleParts)) {
            $title = implode(' ', $titleParts);
        } else {
            // nope, no title was found.
            $title = '';
        }

        return $title;
    }

    /**
     * Get the columnname of the title, name, caption or subject.
     *
     * @return array
     */
    public function getTitleColumnName()
    {
        // If we specified a specific fieldname or array of fieldnames as 'title'.
        if (!empty($this->contenttype['title_format'])) {
            return (array) $this->contenttype['title_format'];
        }

        // Sets the names of some 'common' names for the 'title' column.
        $names = ['title', 'name', 'caption', 'subject'];

        // Some localised options as well
        $names = array_merge($names, ['titel', 'naam', 'onderwerp']); // NL
        $names = array_merge($names, ['nom', 'sujet']); // FR
        $names = array_merge($names, ['nombre', 'sujeto']); // ES

        foreach ($names as $name) {
            if (isset($this->values[$name])) {
                return [$name];
            }
        }

        // Otherwise, grab the first field of type 'text', and assume that's the title.
        if (!empty($this->contenttype['fields'])) {
            foreach ($this->contenttype['fields'] as $key => $field) {
                if ($field['type'] === 'text') {
                    return [$key];
                }
            }
        }

        // Nope, no title was found.
        return [];
    }

    /**
     * Check if a ContentType field has a template set.
     *
     * @return bool
     */
    public function hasTemplateFields()
    {
        if (!is_array($this->contenttype)) {
            return false;
        }

        return !$this->contenttype['viewless'] && $this->getTemplateFieldConfig();
    }

    /**
     * Get the template associate with a ContentType field.
     *
     * @return string
     */
    protected function getTemplateFieldsContentType()
    {
        if (!is_array($this->contenttype)) {
            return '';
        }

        if ($config = $this->getTemplateFieldConfig()) {
            return $config;
        }

        return '';
    }

    private function getTemplateFieldConfig()
    {
        if (!$templateFieldsConfig = $this->app['config']->get('theme/templatefields')) {
            return null;
        }

        /** @var \Bolt\TemplateChooser $templateChooser */
        $templateChooser = $this->app['templatechooser'];
        $templates = $templateChooser->record($this);
        /** @var Environment $twig */
        $twig = $this->app['twig'];

        try {
            $template = $twig->resolveTemplate($templates);
        } catch (LoaderError $e) {
            return null;
        }

        $name = $template->getSourceContext()->getName();

        if (!array_key_exists($name, $templateFieldsConfig)) {
            return null;
        }

        return $templateFieldsConfig[$name];
    }
}
