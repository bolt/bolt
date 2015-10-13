<?php

namespace Bolt\Controller\Async;

use Bolt\Helpers\Input;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
        if (!$this->checkAntiCSRFToken($request->get('bolt_csrf_token'))) {
            $this->app->abort(Response::HTTP_BAD_REQUEST, Trans::__('Something went wrong'));
        }

        $contentType = $request->get('contenttype');
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

        $referer = Request::create($request->server->get('HTTP_REFERER'));
        $order = $referer->query->get('order');
        $page = $referer->query->get('page');
        $filter = $referer->query->get('filter');
        $taxonomy = null;
        foreach (array_keys($this->getOption('taxonomy', [])) as $taxonomyKey) {
            if ($referer->query->get('taxonomy-' . $taxonomyKey)) {
                $taxonomy[$taxonomyKey] = $referer->query->get('taxonomy-' . $taxonomyKey);
            }
        }

        $context = [
            'contenttype'     => $this->getContentType($contentType),
            'multiplecontent' => $this->app['storage.request.listing']->action($contentType, $order, $page, $taxonomy, $filter),
            'filter'          => array_merge((array) $taxonomy, (array) $filter),
            'permissions'     => $this->getContentTypeUserPermissions($contentType, $this->users()->getCurrentUser())
        ];

        return $this->render('@bolt/overview/overview.twig', $context);
    }

    /**
     * Modify an individual ContentType's records.
     *
     * @param string $contentTypeSlug
     * @param array  $recordIds
     */
    protected function modifyContentType($contentTypeSlug, array $recordIds)
    {
        foreach ($recordIds as $recordId => $actionData) {
            if ($actionData === null) {
                continue;
            }

            $repo = $this->getRepository($contentTypeSlug);
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
        if (!$this->isAllowed("contenttype:$contentTypeSlug:delete:$recordId")) {
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
        $canModify = $this->isAllowed("contenttype:$contentTypeSlug:edit:$recordId");
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
        $canTransition = $this->users()->isContentStatusTransitionAllowed($entity->getStatus(), $newStatus, $contentTypeSlug, $entity->getId());
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
        $canChangeOwner = $this->isAllowed("contenttype:$contentTypeSlug:change-ownership:$recordId");
        if (!$canChangeOwner) {
            return;
        }
        $entity->setOwnerid($ownerId);
        $entity->_modified = true;
    }
}
