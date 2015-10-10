<?php

namespace Bolt\Controller\Async;

use Bolt\Helpers\Input;
use Bolt\Storage\Entity\Content;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * Async controller for record manipulation routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Records extends AsyncBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->method('POST');

        $c->post('/content/{action}', 'modify')
            ->bind('contentaction');
    }

    /**
     * Perform an action on a Contenttype record.
     *
     * The action part of the POST request should take the form:
     * [
     *     contenttype => [
     *         id => [
     *             action => [field => value]
     *         ]
     *     ]
     * ]
     *
     * For example:
     * [
     *     'pages'   => [
     *         3 => ['modify' => ['status' => 'held']],
     *         5 => null,
     *         4 => ['modify' => ['status' => 'draft']],
     *         1 => ['delete' => null],
     *         2 => ['modify' => ['status' => 'published']],
     *     ],
     *     'entries' => [
     *         4 => ['modify' => ['status' => 'published']],
     *         1 => null,
     *         5 => ['delete' => null],
     *         2 => null,
     *         3 => ['modify' => ['title' => 'Drop Bear Attacks']],
     *     ]
     * ]
     *
     * @param Request $request Symfony Request
     *
     * @return Response
     */
    public function modify(Request $request)
    {
        $actionData = $request->get('modifications');

        if ($actionData === null) {
            throw new \UnexpectedValueException('No content action data provided in the request.');
        }

        foreach ($actionData as $contentTypeSlug => $recordIds) {
            if (!$this->getContentType($contentTypeSlug)) {
// sprintf('Attempt to modify invalid ContentType: %s', $contentTypeSlug);
                continue;
            } else {
                $this->modifyContentType($contentTypeSlug, $recordIds);
            }
        }

        return $response;
    }

    /**
     * Modify an individual ContentType's records.
     *
     * @param string $contentTypeSlug
     * @param array  $recordIds
     */
    protected function modifyContentType($contentTypeSlug, array $recordIds)
    {
        foreach ($recordIds as $recordId => $actions) {
            if ($actions === null) {
                continue;
            }

            foreach ($actions as $action => $fieldData) {
                if ($action === 'delete') {
                    return $this->deleteRecord($contentTypeSlug, $recordId);
                } else {
                    return $this->modifyRecord($contentTypeSlug, $recordId, $fieldData);
                }
            }
        }
    }

    /**
     * Execute the deletion of a record.
     *
     * @param string $contentTypeSlug
     * @param array  $recordIds
     */
    protected function deleteRecord($contentTypeSlug, $recordId)
    {
        if (!$this->isAllowed("contenttype:$contentTypeSlug:delete:$recordId")) {
            return;
        }

        $repo = $this->getRepository($contentTypeSlug);
        if ($entity = $repo->find($recordId)) {
            $repo->delete($entity);
        }
    }

    /**
     * Modify a record's value(s).
     *
     * @param string  $contentTypeSlug
     * @param integer $recordId
     * @param array   $fieldData
     */
    protected function modifyRecord($contentTypeSlug, $recordId, $fieldData)
    {
        $modified = false;
        $repo = $this->getRepository($contentTypeSlug);

        foreach ($fieldData as $values) {
            $entity = $repo->find($recordId);
            if (!$entity) {
                continue;
            }

            foreach ($values as $field => $value) {
                if (strtolower($field) === 'status') {
                    $modified = $this->transistionRecordStatus($contentTypeSlug, $entity, $value);
                } elseif (strtolower($field) === 'ownerid') {
                    $modified = $this->transistionRecordOwner($contentTypeSlug, $entity, $value);
                } else {
                    $modified = $this->modifyRecordValue($entity, $field, $value);
                }
            }

            if ($modified) {
                if ($repo->save($entity)) {
// $this->flashes()->info(Trans::__("Content '%title%' has been changed to '%newStatus%'", ['%title%' => $title, '%newStatus%' => $newStatus]));
                } else {
// $this->flashes()->info(Trans::__("Content '%title%' could not be modified.", ['%title%' => $title]));
                }
            }
        }
    }

    /**
     * Modify a record's value if permitted.
     *
     * @param Entity $entity
     * @param string $field
     * @param mixed  $value
     *
     * @return boolean
     */
    protected function modifyRecordValue($entity, $field, $value)
    {
        $recordId = $entity->getId();
        $contentTypeSlug = (string) $entity->getContenttype();
        $canModify = $this->isAllowed("contenttype:$contentTypeSlug:edit:$recordId");
        if (!$canModify) {
            return false;
        }
        $entity->$field = Input::cleanPostedData($value);

        return true;
    }

    /**
     * Transition a record's status if permitted.
     *
     * @param string $contentTypeSlug
     * @param Entity $entity
     * @param string $newStatus
     *
     * @return boolean
     */
    protected function transistionRecordStatus($contentTypeSlug, $entity, $newStatus)
    {
        $canTransition = $this->users()->isContentStatusTransitionAllowed($entity->getStatus(), $newStatus, $contentTypeSlug, $entity->getId());
        if (!$canTransition) {
            return false;
        }
        $entity->setStatus($newStatus);

        return true;
    }

    /**
     * Transition a record's owner if permitted.
     *
     * @param string  $contentTypeSlug
     * @param Entity  $entity
     * @param integer $ownerId
     *
     * @return boolean
     */
    protected function transistionRecordOwner($contentTypeSlug, $entity, $ownerId)
    {
        $recordId = $entity->getId();
        $canChangeOwner = $this->isAllowed("contenttype:$contentTypeSlug:change-ownership:$recordId");
        if (!$canChangeOwner) {
            return false;
        }
        $entity->setOwnerid($ownerId);

        return true;
    }
}
