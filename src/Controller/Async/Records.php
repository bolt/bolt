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
}
