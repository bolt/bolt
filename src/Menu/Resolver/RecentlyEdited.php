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
 * @internal Backwards compatibility not guaranteed on this class presently.
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
            $this->addRecentlyEdited($contentMenu, $name, $contentTypes);
        }
    }

    /**
     * @param MenuEntry $contentMenu
     * @param string    $contentTypeKey
     * @param Bag       $contentTypes
     */
    private function addRecentlyEdited(MenuEntry $contentMenu, $contentTypeKey, Bag $contentTypes)
    {
        $entities = $this->getRecords($contentTypeKey, 4);
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
            $label = Str::replaceFirst(Excerpt::createFromEntity($entity, $contentTypes, 80, $this->markdown), '</b>', '&nbsp;</b>');

            /**@var Entity\Content $entity */
            $listingMenu->add(
                MenuEntry::create($entity->getSlug())
                    ->setRoute('editcontent', ['contenttypeslug' => $contentTypeKey, 'id' => $entity->getId()])
                    ->setLabel($label)
                    ->setIcon($contentType->get('icon_one', 'fa:file-text-o'))
            );
        }
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

        try {
            return $repo->findWith($qb);
        } catch (TableNotFoundException $e) {
            return null;
        }
    }
}
