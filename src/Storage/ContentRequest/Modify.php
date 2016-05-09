<?php

namespace Bolt\Storage\ContentRequest;

use Bolt\Helpers\Input;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;
use Bolt\Users;
use Psr\Log\LoggerInterface;

/**
 * Helper class for ContentType record (mass) field modifications and status
 * transitions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Modify
{
    /** @var EntityManager */
    protected $em;
    /** @var Users */
    protected $users;
    /** @var LoggerInterface */
    protected $loggerSystem;
    /** @var FlashLoggerInterface */
    protected $loggerFlash;

    /**
     * Constructor function.
     *
     * @param EntityManager        $em
     * @param Users                $users
     * @param LoggerInterface      $loggerSystem
     * @param FlashLoggerInterface $loggerFlash
     */
    public function __construct(EntityManager $em, Users $users, LoggerInterface $loggerSystem, FlashLoggerInterface $loggerFlash)
    {
        $this->em = $em;
        $this->users = $users;
        $this->loggerSystem = $loggerSystem;
        $this->loggerFlash = $loggerFlash;
    }

    /**
     * Modify an individual ContentType's records.
     *
     * @param string $contentTypeName ContentType slug
     * @param array  $changeRequest   Change array in the format of:
     *                                [id => [action => [field => value]]]
     */
    public function action($contentTypeName, array $changeRequest)
    {
        foreach ($changeRequest as $recordId => $actionData) {
            if ($actionData === null) {
                continue;
            }

            $repo = $this->em->getRepository($contentTypeName);
            foreach ($actionData as $action => $fieldData) {
                if (!$entity = $repo->find($recordId)) {
                    continue;
                }
                $this->modifyContentTypeRecord($repo, $entity, $action, $fieldData);
            }
        }
    }

    /**
     * Perform modification action(s) on a ContentType record.
     *
     * @param Repository $repo
     * @param Content    $entity
     * @param string     $action
     * @param array|null $fieldData
     *
     * @return boolean
     */
    protected function modifyContentTypeRecord(Repository $repo, Content $entity, $action, $fieldData)
    {
        if ($action === 'delete') {
            return $this->deleteRecord($repo, $entity);
        } elseif ($action === 'modify' && $fieldData !== null) {
            $this->modifyRecord($entity, $fieldData);

            if ($entity->_modified === true) {
                return $repo->save($entity);
            }
        }
    }

    /**
     * Execute the deletion of a record.
     *
     * @param Repository $repo
     * @param Content    $entity
     *
     * @return boolean
     */
    protected function deleteRecord(Repository $repo, Content $entity)
    {
        $recordId = $entity->getId();
        $contentTypeName = (string) $entity->getContenttype();
        if (!$this->users->isAllowed("contenttype:$contentTypeName:delete:$recordId")) {
            $this->loggerFlash->error(Trans::__('general.access-denied.content-not-modified', ['%title%' => $entity->getTitle()]));

            return;
        }

        return $repo->delete($entity);
    }

    /**
     * Modify a record's value(s).
     *
     * @param Content $entity
     * @param array   $fieldData
     */
    protected function modifyRecord(Content $entity, array $fieldData)
    {
        foreach ($fieldData as $field => $value) {
            if (strtolower($field) === 'status') {
                $this->transistionRecordStatus($entity, $value);
            } elseif (strtolower($field) === 'ownerid') {
                $this->transistionRecordOwner($entity, $value);
            } else {
                $this->modifyRecordValue($entity, $field, $value);
            }
        }
    }

    /**
     * Modify a record's value if permitted.
     *
     * @param Content $entity
     * @param string  $field
     * @param mixed   $value
     */
    protected function modifyRecordValue(Content $entity, $field, $value)
    {
        $recordId = $entity->getId();
        $contentTypeName = (string) $entity->getContenttype();
        $canModify = $this->users->isAllowed("contenttype:$contentTypeName:edit:$recordId");
        if (!$canModify) {
            $this->loggerFlash->error(Trans::__('general.access-denied.content-not-modified', ['%title%' => $entity->getTitle()]));

            return;
        }
        $entity->$field = Input::cleanPostedData($value);
        $entity->_modified = true;
    }

    /**
     * Transition a record's status if permitted.
     *
     * @param Content $entity
     * @param string  $newStatus
     */
    protected function transistionRecordStatus(Content $entity, $newStatus)
    {
        $contentTypeName = (string) $entity->getContenttype();
        $canTransition = $this->users->isContentStatusTransitionAllowed($entity->getStatus(), $newStatus, $contentTypeName, $entity->getId());
        if (!$canTransition) {
            $this->loggerFlash->error(Trans::__('general.access-denied.content-not-modified', ['%title%' => $entity->getTitle()]));

            return;
        }
        $entity->setStatus($newStatus);
        $entity->_modified = true;
    }

    /**
     * Transition a record's owner if permitted.
     *
     * @param Content $entity
     * @param integer $ownerId
     */
    protected function transistionRecordOwner(Content $entity, $ownerId)
    {
        $recordId = $entity->getId();
        $contentTypeName = (string) $entity->getContenttype();
        $canChangeOwner = $this->users->isAllowed("contenttype:$contentTypeName:change-ownership:$recordId");
        if (!$canChangeOwner) {
            $this->loggerFlash->error(Trans::__('general.access-denied.content-not-modified', ['%title%' => $entity->getTitle()]));

            return;
        }
        $entity->setOwnerid($ownerId);
        $entity->_modified = true;
    }
}
