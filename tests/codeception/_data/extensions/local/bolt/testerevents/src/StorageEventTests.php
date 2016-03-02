<?php

namespace Bolt\Extension\Bolt\TesterEvents;

use Bolt\Events\StorageEvent;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Silex\Application;

class StorageEventTests
{
    /** @var Application */
    private $entityManager;

    /**
     * Constructor.
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Pre-save testing event.
     *
     * @param StorageEvent $event
     */
    public function eventPreSave(StorageEvent $event)
    {
        $contentType = $event->getContentType();

        if ($contentType === 'pages') {
            /** @var Content $record */
            $record = $event->getContent();
            $values = $record->serialize();

            if ($event->isCreate()) {
                // Make the new title uppercase
                $record->setTitle(strtoupper($values['title']));

                // Add a unique paragraph to the end of the teaser
                $record->setTeaser($values['teaser'] . '<p>Snuck in to teaser during PRE_SAVE on create: ' . date('Y-m-d H:i:s') . '</p>');
            } else {
                // Uppercase the first character of each word
                $record->setTitle(ucwords(strtolower($values['title'])));

                // Add a unique paragraph to the end of the teaser
                $record->setTeaser($values['teaser'] . '<p>Added to teaser during PRE_SAVE on save: ' . date('Y-m-d H:i:s') . '</p>');
            }
        }
    }

    /**
     * Post-save testing event.
     *
     * @param StorageEvent $event
     */
    public function eventPostSave(StorageEvent $event)
    {
        $contentType = $event->getContentType();

        if ($contentType === 'pages') {
            $repo = $this->entityManager->getRepository($contentType);
            /** @var Content $record */
            $record = $event->getContent();
            $values = $record->serialize();

            if ($event->isCreate()) {
                // Add a unique paragraph to the end of the body
                $record->setBody($values['body'] . '<p>Snuck in to body during POST_SAVE on create: ' . date('Y-m-d H:i:s') . '</p>');
            } else {
                // Add a unique paragraph to the end of the body
                $record->setBody($values['body'] . '<p>Added to body during POST_SAVE on save: ' . date('Y-m-d H:i:s') . '</p>');
            }

            // Save the changes to the database
            $repo->save($record, true);
        }
    }

    /**
     * Pre-delete testing event.
     *
     * @param StorageEvent $event
     */
    public function eventPreDelete(StorageEvent $event)
    {
    }

    /**
     * Post-delete testing event.
     *
     * @param StorageEvent $event
     */
    public function eventPostDelete(StorageEvent $event)
    {
    }
}
