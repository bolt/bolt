<?php

namespace Bolt\Form\FieldType;

use Bolt\AccessControl\Permissions;
use Bolt\AccessControl\Token\Token;
use Bolt\Common\Deprecated;
use Bolt\Translation\Translator as Trans;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * User permission/role type.
 *
 * @deprecated Since 3.4. Do not use
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class UserRoleType extends AbstractType
{
    /** @var Permissions */
    private $permissions;
    /** @var SessionInterface */
    private $session;

    /**
     * Constructor.
     *
     * @param SessionInterface $session
     * @param Permissions      $permissions
     */
    public function __construct(SessionInterface $session, Permissions $permissions)
    {
        $this->session = $session;
        $this->permissions = $permissions;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        Deprecated::cls(3.4);

        $roles = array_flip(array_map(
            function ($role) {
                return $role['label'];
            },
            $this->permissions->getDefinedRoles()
        ));

        $resolver->setDefaults(
            [
                'label'             => Trans::__('page.edit-users.label.assigned-roles'),
                'attr'              => [],
                'constraints'       => [],
                'choices_as_values' => true, // Can be removed when symfony/form:^3.0 is the minimum
                'choices'           => $roles,
                'choice_attr'       => $this->getRoleAccessCallback(),
                'expanded'          => true,
                'multiple'          => true,
            ]
        );
    }

    /**
     * Callback to disable role choices that a user doesn't have access to
     * manipulate.
     *
     * @return callable
     */
    protected function getRoleAccessCallback()
    {
        return function ($key, $val, $index) {
            /** @var Token $sessionAuth */
            if (!$this->session->isStarted()) {
                return ['disabled' => 'disabled'];
            }
            $sessionAuth = $this->session->get('authentication');
            $currentUser = $sessionAuth->getUser();
            $allowedRoles = $this->permissions->getManipulatableRoles($currentUser->toArray());

            return in_array($key, $allowedRoles) ? [] : ['disabled' => 'disabled'];
        };
    }
}
