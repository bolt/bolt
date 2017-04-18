<?php

namespace Bolt\Nut\Style;

use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Nut custom style.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class NutStyle extends SymfonyStyle
{
    /**
     * {@inheritdoc}
     */
    public function isQuiet()
    {
        return self::VERBOSITY_QUIET === $this->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function isVerbose()
    {
        return self::VERBOSITY_VERBOSE <= $this->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function isVeryVerbose()
    {
        return self::VERBOSITY_VERY_VERBOSE <= $this->getVerbosity();
    }

    /**
     * {@inheritdoc}
     */
    public function isDebug()
    {
        return self::VERBOSITY_DEBUG <= $this->getVerbosity();
    }
}
