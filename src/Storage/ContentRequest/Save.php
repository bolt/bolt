<?php

namespace Bolt\Storage\ContentRequest;

use Bolt\Config;
use Bolt\Exception\AccessControlException;
use Bolt\Helpers\Input;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Storage\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Translation\Translator as Trans;
use Bolt\Users;
use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Helper class for ContentType record editor saves.
 *
 * Prior to v3.0 this functionality existed in \Bolt\Controllers\Backend::editcontent().
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Save
{
    /** @var EntityManager */
    protected $em;
    /** @var Config */
    protected $config;
    /** @var Users */
    protected $users;
    /** @var LoggerInterface */
    protected $loggerChange;
    /** @var LoggerInterface */
    protected $loggerSystem;
    /** @var FlashLoggerInterface */
    protected $loggerFlash;
    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

    /**
     * Constructor function.
     *
     * @param EntityManager         $em
     * @param Config                $config
     * @param Users                 $users
     * @param LoggerInterface       $loggerChange
     * @param LoggerInterface       $loggerSystem
     * @param FlashLoggerInterface  $loggerFlash
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        EntityManager $em,
        Config $config,
        Users $users,
        LoggerInterface $loggerChange,
        LoggerInterface $loggerSystem,
        FlashLoggerInterface $loggerFlash,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->em = $em;
        $this->config = $config;
        $this->users = $users;
        $this->loggerChange = $loggerChange;
        $this->loggerSystem = $loggerSystem;
        $this->loggerFlash = $loggerFlash;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Do the save for a POSTed record.
     *
     * @param array   $formValues
     * @param array   $contenttype  The contenttype data
     * @param integer $id           The record ID
     * @param boolean $new          If TRUE this is a new record
     * @param string  $returnTo
     * @param string  $editReferrer
     *
     * @throws AccessControlException
     *
     * @return Response
     */
    public function action(array $formValues, array $contenttype, $id, $new, $returnTo, $editReferrer)
    {
        $contentTypeSlug = $contenttype['slug'];
        $repo = $this->em->getRepository($contentTypeSlug);

        // If we have an ID now, this is an existing record
        if ($id) {
            $content = $repo->find($id);
            $oldContent = clone $content;
            $oldStatus = $content['status'];
        } else {
            $content = $repo->create(['contenttype' => $contentTypeSlug, 'status' => $contenttype['default_status']]);
            $oldContent = null;
            $oldStatus = 'draft';
        }

        // Don't allow spoofing the ID.
        if ($content->getId() !== null && (integer) $id !== $content->getId()) {
            if ($returnTo === 'ajax') {
                throw new AccessControlException("Don't try to spoof the id!");
            }
            $this->loggerFlash->error("Don't try to spoof the id!");

            return new RedirectResponse($this->generateUrl('dashboard'));
        }

        $this->setPostedValues($content, $formValues, $contenttype);
        $this->setTransitionStatus($content, $contentTypeSlug, $id, $oldStatus);

        // Get the associated record change comment
        $comment = isset($formValues['changelog-comment']) ? $formValues['changelog-comment'] : '';

        // Save the record
        return $this->saveContentRecord($content, $oldContent, $contenttype, $new, $comment, $returnTo, $editReferrer);
    }

    /**
     * Check whether the status is allowed.
     *
     * We act as if a status *transition* were requested and fallback to the old
     * status otherwise.
     *
     * @param Entity\Entity $content
     * @param string        $contentTypeSlug
     * @param integer       $id
     * @param string        $oldStatus
     */
    private function setTransitionStatus(Entity\Entity $content, $contentTypeSlug, $id, $oldStatus)
    {
        $canTransition = $this->users->isContentStatusTransitionAllowed($oldStatus, $content->getStatus(), $contentTypeSlug, $id);
        if (!$canTransition) {
            $content->setStatus($oldStatus);
        }
    }

    /**
     * Set a ContentType record values from a HTTP POST.
     *
     * @param Entity\Content $content
     * @param array          $formValues
     * @param array          $contentType
     *
     * @throws AccessControlException
     */
    private function setPostedValues(Entity\Content $content, $formValues, $contentType)
    {
        // Ensure all fields have valid values
        $formValues = $this->setSuccessfulControlValues($formValues, $contentType['fields']);
        $formValues = Input::cleanPostedData($formValues);
        unset($formValues['contenttype']);

        $user = $this->users->getCurrentUser();
        if ($id = $content->getId()) {
            // Owner is set explicitly, is current user is allowed to do this?
            if (isset($formValues['ownerid']) && (integer) $formValues['ownerid'] !== $content->getOwnerid()) {
                if (!$this->users->isAllowed("contenttype:{$contentType['slug']}:change-ownership:$id")) {
                    throw new AccessControlException('Changing ownership is not allowed.');
                }
                $content->setOwnerid($formValues['ownerid']);
            }
        } else {
            $content->setOwnerid($user['id']);
        }

        // Hack … remove soon
        $formValues += ['status' => 'draft'];
        // Make sure we have a proper status.
        if (!in_array($formValues['status'], ['published', 'timed', 'held', 'draft'])) {
            if ($status = $content->getStatus()) {
                $formValues['status'] = $status;
            } else {
                $formValues['status'] = 'draft';
            }
        }

        // Set the object values appropriately
        foreach ($formValues as $name => $value) {
            if ($name === 'relation' || $name === 'taxonomy') {
                continue;
            } else {
                $content->set($name, empty($value) ? null : $value);
            }
        }
        $this->setPostedRelations($content, $formValues);
        $this->setPostedTaxonomies($content, $formValues);
    }

    /**
     * Convert POST relationship values to an array of Entity objects keyed by
     * ContentType.
     *
     * @param Entity\Content $content
     * @param array|null     $formValues
     */
    private function setPostedRelations(Entity\Content $content, $formValues)
    {
        $related = $this->em->createCollection('Bolt\Storage\Entity\Relations');
        $related->setFromPost($formValues, $content);
        $content->setRelation($related);
    }

    /**
     * Set valid POST taxonomies.
     *
     * @param Entity\Content $content
     * @param array|null     $formValues
     */
    private function setPostedTaxonomies(Entity\Content $content, $formValues)
    {
        $taxonomies = $this->em->createCollection('Bolt\Storage\Entity\Taxonomy');
        $taxonomies->setFromPost($formValues, $content);
        $content->setTaxonomy($taxonomies);
    }

    /**
     * Commit the record to the database.
     *
     * @param Entity\Content      $content
     * @param Entity\Content|null $oldContent
     * @param array               $contentType
     * @param boolean             $new
     * @param string              $comment
     * @param string              $returnTo
     * @param string              $editReferrer
     *
     * @return Response
     */
    private function saveContentRecord(Entity\Content $content, $oldContent, array $contentType, $new, $comment, $returnTo, $editReferrer)
    {
        // Save the record
        $repo = $this->em->getRepository($contentType['slug']);

        // Update the date modified timestamp
        $content->setDatechanged('now');

        $repo->save($content);
        $id = $content->getId();

        // Create the change log entry if configured
        $this->logChange($contentType, $content->getId(), $content, $oldContent, $comment);

        // Log the change
        if ($new) {
            $this->loggerFlash->success(Trans::__('contenttypes.generic.saved-new', ['%contenttype%' => $contentType['slug']]));
            $this->loggerSystem->info('Created: ' . $content->getTitle(), ['event' => 'content']);
        } else {
            $this->loggerFlash->success(Trans::__('contenttypes.generic.saved-changes', ['%contenttype%' => $contentType['slug']]));
            $this->loggerSystem->info('Saved: ' . $content->getTitle(), ['event' => 'content']);
        }

        /*
         * We now only get a returnto parameter if we are saving a new
         * record and staying on the same page, i.e. "Save {contenttype}"
         */
        if ($returnTo) {
            if ($returnTo === 'new') {
                return new RedirectResponse(
                    $this->generateUrl(
                        'editcontent',
                        [
                            'contenttypeslug' => $contentType['slug'],
                            'id'              => $id,
                            '#'               => $returnTo,
                        ]
                    )
                );
            } elseif ($returnTo === 'saveandnew') {
                return new RedirectResponse(
                    $this->generateUrl(
                        'editcontent',
                        [
                            'contenttypeslug' => $contentType['slug'],
                            '#'               => $returnTo,
                        ]
                    )
                );
            } elseif ($returnTo === 'ajax') {
                return $this->createJsonUpdate($content, true);
            } elseif ($returnTo === 'test') {
                return $this->createJsonUpdate($content, false);
            }
        }

        // No returnto, so we go back to the 'overview' for this contenttype.
        // check if a pager was set in the referrer - if yes go back there
        if ($editReferrer) {
            return new RedirectResponse($editReferrer);
        } else {
            return new RedirectResponse($this->generateUrl('overview', ['contenttypeslug' => $contentType['slug']]));
        }
    }

    /**
     * Add successful control values to request values, and do needed corrections.
     *
     * @see http://www.w3.org/TR/html401/interact/forms.html#h-17.13.2
     *
     * @param array $formValues
     * @param array $fields
     *
     * @return array
     */
    private function setSuccessfulControlValues(array $formValues, $fields)
    {
        foreach ($fields as $key => $values) {
            if (isset($formValues[$key])) {
                if ($values['type'] === 'float') {
                    // We allow ',' and '.' as decimal point and need '.' internally
                    $formValues[$key] = str_replace(',', '.', $formValues[$key]);
                }
            } else {
                if ($values['type'] === 'select' && isset($values['multiple']) && $values['multiple'] === true) {
                    $formValues[$key] = [];
                } elseif ($values['type'] === 'checkbox') {
                    $formValues[$key] = 0;
                }
            }
        }

        return $formValues;
    }

    /**
     * Build a valid AJAX response for in-place saves that account for pre/post
     * save events.
     *
     * @param Entity\Content $content
     * @param boolean        $flush
     *
     * @return JsonResponse
     */
    private function createJsonUpdate(Entity\Content $content, $flush)
    {
        /*
         * Flush any buffers from saveConent() dispatcher hooks
         * and make sure our JSON output is clean.
         *
         * Currently occurs due to exceptions being generated in the dispatchers
         * in \Bolt\Storage::saveContent()
         *     StorageEvents::PRE_SAVE
         *     StorageEvents::POST_SAVE
         */
        if ($flush) {
            Response::closeOutputBuffers(0, false);
        }

        $val = $content->toArray();

        if ($val['datechanged'] instanceof Carbon) {
            $val['datechanged'] = $val['datechanged']->toIso8601String();
        } elseif (isset($val['datechanged'])) {
            $val['datechanged'] = (new Carbon($val['datechanged']))->toIso8601String();
        }

        // Adjust decimal point as some locales use a comma and… JavaScript
        $lc = localeconv();
        $fields = $this->config->get('contenttypes/' . $content->getContenttype() . '/fields');
        foreach ($fields as $key => $values) {
            if ($values['type'] === 'float' && $lc['decimal_point'] === ',') {
                $val[$key] = str_replace('.', ',', $val[$key]);
            }
        }

        // Unset flashbag for ajax
        $this->loggerFlash->clear();

        return new JsonResponse($val);
    }

    /**
     * Add a change log entry to track the change.
     *
     * @param string              $contentType
     * @param integer             $contentId
     * @param Entity\Content      $newContent
     * @param Entity\Content|null $oldContent
     * @param string|null         $comment
     */
    private function logChange($contentType, $contentId, $newContent = null, $oldContent = null, $comment = null)
    {
        $type = $oldContent ? 'Update' : 'Insert';

        $this->loggerChange->info(
            $type . ' record',
            [
                'action'      => strtoupper($type),
                'contenttype' => $contentType,
                'id'          => $contentId,
                'new'         => $newContent ? $newContent->toArray() : null,
                'old'         => $oldContent ? $oldContent->toArray() : null,
                'comment'     => $comment,
            ]
        );
    }

    /**
     * Shortcut for {@see UrlGeneratorInterface::generate}
     *
     * @param string $name          The name of the route
     * @param array  $params        An array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string
     */
    private function generateUrl($name, $params = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        /** @var UrlGeneratorInterface $generator */
        $generator = $this->urlGenerator;

        return $generator->generate($name, $params, $referenceType);
    }
}
