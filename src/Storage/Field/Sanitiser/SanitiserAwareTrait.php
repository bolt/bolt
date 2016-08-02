<?php

namespace Bolt\Storage\Field\Sanitiser;

/**
 * Sanitiser aware trait.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait SanitiserAwareTrait
{
    /** @var SanitiserInterface */
    protected $santiser;

    /**
     * Return the sanitiser object.
     *
     * @return SanitiserInterface
     */
    public function getSanitiser()
    {
        return $this->santiser;
    }

    /**
     * Set the sanitiser.
     *
     * @param SanitiserInterface $sanitiser
     */
    public function setSanitiser(SanitiserInterface $sanitiser)
    {
        $this->santiser = $sanitiser;
    }
}
