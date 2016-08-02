<?php

namespace Bolt\Storage\Field\Sanitiser;

/**
 * Sanitiser aware interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface SanitiserAwareInterface
{
    /**
     * Return the sanitiser object.
     *
     * @return SanitiserInterface
     */
    public function getSanitiser();

    /**
     * Set the sanitiser.
     *
     * @param SanitiserInterface $sanitiser
     */
    public function setSanitiser(SanitiserInterface $sanitiser);
}
