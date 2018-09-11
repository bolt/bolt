<?php

namespace Bolt\Menu\Resolver;

use Bolt\Collection\Bag;
use Bolt\Common\Str;
use Bolt\Helpers\Excerpt;
use Bolt\Menu\MenuEntry;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Parsedown;

/**
 * Recently edited record resolver.
 *
 * @internal backwards compatibility not guaranteed on this class presently
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class RecentlyEdited
{
    /** @var EntityManager */
    private $em;
    /** @var Parsedown */
    private $markdown;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param Parsedown     $markdown
     */
    public function __construct(EntityManager $em, Parsedown $markdown)
    {
        $this->em = $em;
        $this->markdown = $markdown;
    }

    /**
     * @param MenuEntry $menu
     * @param Bag       $contentTypes
     */
    public function resolve(MenuEntry $menu, Bag $contentTypes)
    {
        $contentRoot = $menu->get('content');
        if (!$contentRoot->has('main')) {
            return;
        }
        foreach ($contentRoot->get('main')->children() as $name => $contentMenu) {
            if ($contentMenu->isGroup()) {
                $this->resolveGroupMenu($contentMenu, $contentTypes);
                continue;
            }
            if ($contentTypes->getPath($name . '/singleton')) {
                $this->addSingleton($contentMenu, $name);
                continue;
            }
            $this->addRecentlyEdited($contentMenu, $name, $contentTypes);
        }
    }

    /**
     * @param MenuEntry $groupMenu
     * @param Bag       $contentTypes
     */
    private function resolveGroupMenu(MenuEntry $groupMenu, Bag $contentTypes)
    {
        foreach ($groupMenu->children() as $name => $contentMenu) {
            if ($contentTypes->getPath($name . '/singleton')) {
                $this->addSingleton($contentMenu, $name);
            }
        }
    }

    /**
     * @param MenuEntry $contentMenu
     * @param string    $contentTypeKey
     * @param Bag       $contentTypes
     */
    private function addRecentlyEdited(MenuEntry $contentMenu, $contentTypeKey, Bag $contentTypes)
    {
        try {
            $entities = $this->getRecords($contentTypeKey, 4);
        } catch (TableNotFoundException $e) {
            $contentMenu->parent()->remove($contentMenu->getName());

            return;
        }

        if (!$entities) {
            return;
        }
        // Parent for this ContentType recently edited
        $listingMenu = $contentMenu->add(
            MenuEntry::create('recent')
                ->setPermission('contenttype:' . $contentTypeKey)
        );

        // Each of the ContentType record entries.
        foreach ($entities as $entity) {
            $contentType = Bag::from($contentTypes->get($contentTypeKey));
            $label = Str::replaceFirst(Excerpt::createFromEntity($entity, $contentTypes, 80, $this->markdown), '</b>', '</b>');

            /**@var Entity\Content $entity */
            $listingMenu->add(
                MenuEntry::create($entity->getSlug())
                    ->setRoute('editcontent', ['contenttypeslug' => $contentTypeKey, 'id' => $entity->getId()])
                    ->setLabel($label)
                    ->setIcon($contentType->get('icon_one', 'fa:file-text-o'))
                    ->setPermission('contenttype:' . $contentTypeKey . ':edit')
            );
        }
    }

    /**
     * @param MenuEntry $contentMenu
     * @param string    $contentTypeKey
     */
    private function addSingleton(MenuEntry $contentMenu, $contentTypeKey)
    {
        try {
            $entities = $this->getRecords($contentTypeKey, 1);
        } catch (TableNotFoundException $e) {
            $contentMenu->parent()->remove($contentMenu->getName());

            return;
        }

        $singleton = MenuEntry::create('singleton')
            ->setLabel($contentMenu->getLabel())
            ->setIcon($contentMenu->getIcon())
        ;

        // If there is an existing record, remove the ability to create a new one
        if ($entities) {
            $entity = reset($entities);
            $singleton
                ->setRoute('editcontent', ['contenttypeslug' => $contentTypeKey, 'id' => $entity->getId()])
                ->setPermission('contenttype:' . $contentTypeKey)
            ;
        } else {
            $singleton
                ->setRoute('editcontent', ['contenttypeslug' => $contentTypeKey])
                ->setPermission('contenttype:' . $contentTypeKey . ':create')
            ;
        }

        $contentMenu->add($singleton);

        // We don't need 'view' or 'new' here
        $contentMenu->remove('view');
        $contentMenu->remove('new');
    }

    /**
     * Fetch recently changed records for a given ContentType.
     *
     * @param string $contentTypeKey
     * @param int    $limit
     *
     * @return Entity\Content[]
     */
    private function getRecords($contentTypeKey, $limit)
    {
        $repo = $this->em->getRepository($contentTypeKey);
        $qb = $repo
            ->createQueryBuilder()
            ->setMaxResults($limit)
            ->orderBy('datechanged', 'DESC')
        ;

        return $repo->findWith($qb);
    }
}
