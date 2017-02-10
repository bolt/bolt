<?php

namespace Bolt\Helpers;

use RandomLib;

/**
 * @internal This is removed in 3.3.
 *
 * Don't load mcrypt mixers as they are deprecated in PHP 7.1
 * We weren't using them anyways.
 */
class RandomLibFactory extends RandomLib\Factory
{
    /**
     * {@inheritdoc}
     */
    public function registerMixer($name, $class)
    {
        if (strpos($name, 'Mcrypt') === false) {
            parent::registerMixer($name, $class);
        }

        return $this;
    }
}
