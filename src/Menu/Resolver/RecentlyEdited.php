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
     * @param Bag       $contentType
     */
    public function resolve(MenuEntry $menu, Bag $contentType)
    {
        $contentRoot = $menu->get('content');
        if (!$contentRoot->has('main')) {
            return;
        }

        foreach ($contentRoot->get('main')->children() as $name => $contentMenu) {
            $this->addRecentlyEdited($contentMenu, $name, $contentType);
        }
    }

    /**
     * @param MenuEntry $contentMenu
     * @param string    $contentTypeKey
     * @param Bag       $contentType
     */
    private function addRecentlyEdited(MenuEntry $contentMenu, $contentTypeKey, Bag $contentType)
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
            $label = Str::replaceFirst(Excerpt::createFromEntity($entity, 80, $this->markdown), '</b>', '&nbsp;</b>');

            /**@var Entity\Content $entity */
            $listingMenu->add(
                MenuEntry::create($entity->getSlug())
                    ->setRoute('editcontent', ['contenttypeslug' => $contentTypeKey, 'id' => $entity->getId()])
                    ->setLabel($label)
                    ->setIcon($contentType->getPath($contentTypeKey . '/icon_one', 'fa:file-text-o'))
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
