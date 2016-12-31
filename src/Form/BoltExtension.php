<?php

namespace Bolt\Form;

use Silex\Application;
use Symfony\Component\Form\AbstractExtension;

/**
 * Symfony Forms extension to provide types, type extensions and a guesser.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class BoltExtension extends AbstractExtension
{
    /** @var Application */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
