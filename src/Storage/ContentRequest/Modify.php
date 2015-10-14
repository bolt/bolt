<?php

namespace Bolt\Storage\ContentRequest;

use Bolt\Helpers\Input;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;

/**
 * Helper class for ContentType record (mass) field modifications and status
 * transitions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Modify extends BaseContentRequest
{
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

            $repo = $this->app['storage']->getRepository($contentTypeName);
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
     * @param array      $fieldData
     */
    protected function modifyContentTypeRecord(Repository $repo, Content $entity, $action, array $fieldData)
    {
        if ($action === 'delete') {
            $this->deleteRecord($repo, $entity);
        } elseif ($action === 'modify') {
            $this->modifyRecord($entity, $fieldData);

            if ($entity->_modified === true) {
                if ($repo->save($entity)) {
// $this->app['logger.flash']->info(Trans::__("Content '%title%' has been changed to '%newStatus%'", ['%title%' => $title, '%newStatus%' => $newStatus]));
                } else {
// $this->app['logger.flash']->info(Trans::__("Content '%title%' could not be modified.", ['%title%' => $title]));
                }
            }
        }
    }

    /**
     * Execute the deletion of a record.
     *
     * @param Repository $repo
     * @param Content    $entity
     */
    protected function deleteRecord(Repository $repo, Content $entity)
    {
        $recordId = $entity->getId();
        $contentTypeName = (string) $entity->getContenttype();
        if (!$this->app['users']->isAllowed("contenttype:$contentTypeName:delete:$recordId")) {
            return;
        }
        $repo->delete($entity);
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
        $canModify = $this->app['users']->isAllowed("contenttype:$contentTypeName:edit:$recordId");
        if (!$canModify) {
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
        $canTransition = $this->app['users']->isContentStatusTransitionAllowed($entity->getStatus(), $newStatus, $contentTypeName, $entity->getId());
        if (!$canTransition) {
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
        $canChangeOwner = $this->app['users']->isAllowed("contenttype:$contentTypeName:change-ownership:$recordId");
        if (!$canChangeOwner) {
            return;
        }
        $entity->setOwnerid($ownerId);
        $entity->_modified = true;
    }
}
