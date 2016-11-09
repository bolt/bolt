<?php

namespace Bolt\Storage\Field\Sanitiser;

use Maid\Maid;

/**
 * Field sanitiser class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Sanitiser implements SanitiserInterface
{
    /** @var array */
    protected $allowedTags;
    /** @var array */
    protected $allowedAttributes;
    /** @var array */
    protected $allowedWyswig;

    /**
     * Constructor.
     *
     * @param array $allowedTags
     * @param array $allowedAttributes
     * @param array $allowedWyswig
     */
    public function __construct(array $allowedTags, array $allowedAttributes, array $allowedWyswig)
    {
        $this->allowedTags = $allowedTags;
        $this->allowedAttributes = $allowedAttributes;
        $this->allowedWyswig = $allowedWyswig;
    }

    /**
     * {@inheritdoc}
     */
    public function sanitise($value, $isWysiwyg = false)
    {
        $allowedTags = $isWysiwyg
            ? $this->getWyswigAllowedTags()
            : $this->getAllowedTags();
        
        // Check if the input containts encoded HTML entities. If it does, we'll
        // need to decode the output later. This is because the sanitiser will
        // convert entities in the cleaned HTML, if they aren't present yet.
        // Ideally we'd fix this upstream by using \DomDocument::substituteEntities,
        // but that setting is disregarded in PHP's implementation at least.
        // This leaves us no choice but to implement this crude, albeit contained
        // fix in this location.
        $needsDecodeEntities = ($value === html_entity_decode($value, ENT_NOQUOTES));

        $maid = new Maid(
            [
                'output-format'   => 'html',
                'allowed-tags'    => $allowedTags,
                'allowed-attribs' => $this->getAllowedAttributes(),
            ]
        );

        $output = $maid->clean($value);

        if ($needsDecodeEntities) {
            $output = html_entity_decode($output, ENT_NOQUOTES);
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedTags()
    {
        return $this->allowedTags;
    }

    /**
     * {@inheritdoc}
     */
    public function setAllowedTags(array $allowedTags)
    {
        $this->allowedTags = $allowedTags;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedAttributes()
    {
        return $this->allowedAttributes;
    }

    /**
     * {@inheritdoc}
     */
    public function setAllowedAttributes(array $allowedAttributes)
    {
        $this->allowedAttributes = $allowedAttributes;

        return $this;
    }

    /**
     * Return a list of allowed tags needed for WYSIWYG field types.
     *
     * For HTML fields we want to override a few tags, e.g, it makes
     * no sense to disallow `<iframe>` if we have `embed: true` in
     * config.yml.
     *
     * @return array
     */
    protected function getWyswigAllowedTags()
    {
        $allowedBecauseWysiwyg = [
            'div', 'p', 'br',  'pre', 'a',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'li', 'ul', 'ol',
            'strong', 'em', 'i', 'b',
        ];

        if ($this->isWysiwygEnabled('images')) {
            $allowedBecauseWysiwyg[] = 'img';
        }
        if ($this->isWysiwygEnabled('tables')) {
            $allowedBecauseWysiwyg = array_merge(
                $allowedBecauseWysiwyg,
                ['table', 'tbody', 'thead', 'tfoot', 'th', 'td', 'tr']
            );
        }
        if ($this->isWysiwygEnabled('fontcolor')) {
            $allowedBecauseWysiwyg[] = 'span';
        }
        if ($this->isWysiwygEnabled('subsuper')) {
            $allowedBecauseWysiwyg = array_merge(
                $allowedBecauseWysiwyg,
                ['sub', 'sup']
            );
        }
        if ($this->isWysiwygEnabled('underline')) {
            $allowedBecauseWysiwyg[] = 'u';
        }
        if ($this->isWysiwygEnabled('embed')) {
            // Note: Only <iframe>. Not <script>, <embed> or <object>.
            $allowedBecauseWysiwyg[] = 'iframe';
            // We also need to add a few attributes as well.
            $this->setAllowedAttributes(array_unique(array_merge(
                    $this->getAllowedAttributes(),
                    ['src', 'width', 'height', 'frameborder', 'allowfullscreen', 'scrolling']))
            );
        }
        if ($this->isWysiwygEnabled('ruler')) {
            $allowedBecauseWysiwyg[] = 'hr';
        }
        if ($this->isWysiwygEnabled('strike')) {
            $allowedBecauseWysiwyg[] = 's';
        }
        if ($this->isWysiwygEnabled('blockquote')) {
            $allowedBecauseWysiwyg[] = 'blockquote';
        }
        if ($this->isWysiwygEnabled('codesnippet')) {
            $allowedBecauseWysiwyg = array_merge(
                $allowedBecauseWysiwyg,
                ['code', 'pre', 'tt']
            );
        }

        return array_unique(array_merge($this->getAllowedTags(), $allowedBecauseWysiwyg));
    }

    /**
     * Return a WYSIWYG configuration value.
     *
     * @param string $name
     *
     * @return bool
     */
    protected function isWysiwygEnabled($name)
    {
        return isset($this->allowedWyswig[$name]) && (bool) $this->allowedWyswig[$name];
    }
}
