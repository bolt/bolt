<?php

namespace Bolt\Menu\Resolver;

use Bolt\AccessControl\Permissions;
use Bolt\Menu\MenuEntry;
use Bolt\Storage\Entity;

/**
 * Menu access permission resolver.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class Access
{
    /** @var Permissions */
    private $permissions;

    /**
     * Constructor.
     *
     * @param Permissions $permissions
     */
    public function __construct(Permissions $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * Resolve the access permissions on each menu child recursively, and
     * remove the child if access is not granted. Should a parent have no
     * remaining children & isn't routed, it should then be removed.
     *
     * @param MenuEntry    $menu
     * @param Entity\Users $user
     */
    public function resolve(MenuEntry $menu, Entity\Users $user)
    {
        foreach ($menu->children() as $name => $child) {
            $this->doResolve($menu, $child, $user);

            if ($child->children() === [] && $child->getUri() === null) {
                $menu->remove($name);
            }
        }
    }

    /**
     * @param MenuEntry    $parent
     * @param MenuEntry    $child
     * @param Entity\Users $user
     */
    private function doResolve(MenuEntry $parent, MenuEntry $child, Entity\Users $user)
    {
        $perm = $child->getPermission();
        if ($perm && !$this->permissions->isAllowed($perm, $user->toArray())) {
            $parent->remove($child->getName());

            return;
        }
        foreach ($child->children() as $grandchild) {
            $this->doResolve($child, $grandchild, $user);
        }
    }
}
