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

    /**
     * {@inheritdoc}
     */
    protected function loadTypes()
    {
        return [
            // User editing fields
            new FieldType\UserRoleType($this->app['session'], $this->app['permissions']),

            // Form
            new FormType\UserEditType(),
            new FormType\UserLoginType(),
            new FormType\UserNewType(),
            new FormType\UserProfileType(),
        ];
    }
}
