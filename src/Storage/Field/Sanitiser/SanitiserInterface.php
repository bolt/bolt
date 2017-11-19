<?php

namespace Bolt\Storage\Field\Sanitiser;

/**
 * Field sanitiser interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface SanitiserInterface
{
    /**
     * Sanitise HTML, by allowing only white-listed tags and attributes.
     *
     * @param string $value     a string value to sanitize
     * @param bool   $isWysiwyg true if the field should allow HTML tags
     *                          needed for WYSIWYG fields
     *
     * @return string
     */
    public function sanitise($value, $isWysiwyg = false);

    /**
     * Return the list of allowed HTML tags.
     *
     * @return array
     */
    public function getAllowedTags();

    /**
     * Override the allowed HTML tags.
     *
     * @param array $allowedTags
     *
     * @return SanitiserInterface
     */
    public function setAllowedTags(array $allowedTags);

    /**
     * Return the list of allowed attributes.
     *
     * @return array
     */
    public function getAllowedAttributes();

    /**
     * Override the allowed attributes.
     *
     * @param array $allowedAttributes
     *
     * @return SanitiserInterface
     */
    public function setAllowedAttributes(array $allowedAttributes);
}
