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
     * @param string $contentTypeSlug
     * @param array  $recordIds
     */
    public function action($contentTypeSlug, array $recordIds)
    {
        foreach ($recordIds as $recordId => $actionData) {
            if ($actionData === null) {
                continue;
            }

            $repo = $this->app['storage']->getRepository($contentTypeSlug);
            foreach ($actionData as $action => $fieldData) {
                if (!$entity = $repo->find($recordId)) {
                    continue;
                }
                $this->modifyContentTypeRecord($action, $repo, $entity, $fieldData);
            }
        }
    }

    /**
     * Perform modification action(s) on a ContentType record.
     *
     * @param string     $action
     * @param Repository $repo
     * @param Content    $entity
     * @param array      $fieldData
     */
    protected function modifyContentTypeRecord($action, $repo, $entity, $fieldData)
    {
        if ($action === 'delete') {
            $this->deleteRecord($repo, $entity);
        } elseif ($action === 'modify') {
            $this->modifyRecord($entity, $fieldData);

            if ($entity->_modified === true) {
                if ($repo->save($entity)) {
// $this->flashes()->info(Trans::__("Content '%title%' has been changed to '%newStatus%'", ['%title%' => $title, '%newStatus%' => $newStatus]));
                } else {
// $this->flashes()->info(Trans::__("Content '%title%' could not be modified.", ['%title%' => $title]));
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
    protected function deleteRecord($repo, $entity)
    {
        $recordId = $entity->getId();
        $contentTypeSlug = (string) $entity->getContenttype();
        if (!$this->app['users']->isAllowed("contenttype:$contentTypeSlug:delete:$recordId")) {
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
    protected function modifyRecord($entity, array $fieldData)
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
    protected function modifyRecordValue($entity, $field, $value)
    {
        $recordId = $entity->getId();
        $contentTypeSlug = (string) $entity->getContenttype();
        $canModify = $this->app['users']->isAllowed("contenttype:$contentTypeSlug:edit:$recordId");
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
    protected function transistionRecordStatus($entity, $newStatus)
    {
        $contentTypeSlug = (string) $entity->getContenttype();
        $canTransition = $this->app['users']->isContentStatusTransitionAllowed($entity->getStatus(), $newStatus, $contentTypeSlug, $entity->getId());
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
    protected function transistionRecordOwner($entity, $ownerId)
    {
        $recordId = $entity->getId();
        $contentTypeSlug = (string) $entity->getContenttype();
        $canChangeOwner = $this->app['users']->isAllowed("contenttype:$contentTypeSlug:change-ownership:$recordId");
        if (!$canChangeOwner) {
            return;
        }
        $entity->setOwnerid($ownerId);
        $entity->_modified = true;
    }
}
