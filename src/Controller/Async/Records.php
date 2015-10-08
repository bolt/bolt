<?php

namespace Bolt\Controller\Async;

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

        $c->post('/content/{action}/{contenttypeslug}/{id}', 'modify')
            ->bind('contentaction');
    }

    /**
     * Delete a record.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug
     * @param integer $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Request $request, $contenttypeslug, $id)
    {
        $ids = explode(',', $id);
        $contenttype = $this->getContentType($contenttypeslug);

        foreach ($ids as $id) {
            $content = $this->getContent($contenttype['slug'], ['id' => $id, 'status' => '!undefined']);
            $title = $content->getTitle();

            if (!$this->isAllowed("contenttype:$contenttypeslug:delete:$id")) {
                $this->flashes()->error(Trans::__('Permission denied', []));
            } elseif ($this->checkAntiCSRFToken() && $this->storage()->deleteContent($contenttypeslug, $id)) {
                $this->flashes()->info(Trans::__("Content '%title%' has been deleted.", ['%title%' => $title]));
            } else {
                $this->flashes()->info(Trans::__("Content '%title%' could not be deleted.", ['%title%' => $title]));
            }
        }

        // Get the referer's query parameters
        $queryParams = $this->getRefererQueryParameters($request);
        $queryParams['contenttypeslug'] = $contenttypeslug;

        return $this->redirectToRoute('overview', $queryParams);
    }

    /**
     * Perform an action on a Contenttype record.
     *
     * @param Request $request         The Symfony Request
     * @param string  $contenttypeslug The content type slug
     * @param integer $id              The content ID
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function modify(Request $request, $action, $contenttypeslug, $id)
    {
        if ($action === 'delete') {
            return $this->delete($request, $contenttypeslug, $id);
        }

        // This shouldn't happen
        if (!$this->getContentType($contenttypeslug)) {
            $this->flashes()->error(Trans::__('Attempt to modify invalid Contenttype.'));

            return $this->redirectToRoute('dashboard');
        }

        // Map actions to new statuses
        $actionStatuses = [
            'held'    => 'held',
            'publish' => 'published',
            'draft'   => 'draft',
        ];
        // Map actions to requred permission
        $actionPermissions = [
            'publish' => 'publish',
            'held'    => 'depublish',
            'draft'   => 'depublish',
        ];
        // Get the referer's query parameters
        $queryParams = $this->getRefererQueryParameters($request);
        $queryParams['contenttypeslug'] = $contenttypeslug;

        if (!isset($actionStatuses[$action])) {
            $this->flashes()->error(Trans::__('No such action for content.'));

            return $this->redirectToRoute('overview', $queryParams);
        }

        $newStatus = $actionStatuses[$action];
        $repo = $this->storage()->getRepository($contenttypeslug);
        $record = $repo->find($id);
        $title = $record->getTitle();
        $canModify = $this->isAllowed("contenttype:$contenttypeslug:{$actionPermissions[$action]}:$id");
        $canTransition = $this->users()->isContentStatusTransitionAllowed($record->getStatus(), $newStatus, $contenttypeslug, $id);

        if (!$canModify || !$canTransition) {
            $this->flashes()->error(Trans::__('You do not have the right privileges to %ACTION% that record.', ['%ACTION%' => $actionPermissions[$action]]));

            return $this->redirectToRoute('overview', $queryParams);
        }

        $record->setStatus($newStatus);
        if ($repo->save($record)) {
            $this->flashes()->info(Trans::__("Content '%title%' has been changed to '%newStatus%'", ['%title%' => $title, '%newStatus%' => $newStatus]));
        } else {
            $this->flashes()->info(Trans::__("Content '%title%' could not be modified.", ['%title%' => $title]));
        }

        return $this->redirectToRoute('overview', $queryParams);
    }
}
