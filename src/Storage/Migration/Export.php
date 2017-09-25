<?php

namespace Bolt\Storage\Migration;

use Bolt\Collection\MutableBag;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Entity\Entity;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Repository\ContentRepository;
use Bolt\Version;
use Carbon\Carbon;

/**
 * Database records export class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
final class Export
{
    /** @var EntityManager */
    private $em;
    /** @var Query */
    private $query;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param Query         $query
     */
    public function __construct(EntityManager $em, Query $query)
    {
        $this->em = $em;
        $this->query = $query;
    }

    /**
     * Run the export process and return the data.
     *
     * @param array      $exportContentTypes
     * @param MutableBag $responseBag
     *
     * @return MutableBag
     */
    public function run(array $exportContentTypes, MutableBag $responseBag)
    {
        // Get initial data object
        $exportData = MutableBag::from([]);
        // Add the meta header
        $this->addExportMeta($exportData);

        // Add records for each ContentType
        foreach ($exportContentTypes as $contentTypeName) {
            $exportData->set($contentTypeName, MutableBag::from([]));
            $this->getRecords($contentTypeName, $exportData, $responseBag);
        }

        return $exportData;
    }

    /**
     * Add the export meta information.
     *
     * @param MutableBag $exportData
     */
    private function addExportMeta(MutableBag $exportData)
    {
        $exportData->set('__bolt_export_meta', [
            'date'     => Carbon::now()->toRfc3339String(),
            'version'  => Version::forComposer(),
            'platform' => $this->em->getConnection()->getDatabasePlatform()->getName(),
        ]);
    }

    /**
     * Get the records for a given ContentType.
     *
     * @param string     $contentTypeName
     * @param MutableBag $exportData
     * @param MutableBag $responseBag
     */
    private function getRecords($contentTypeName, MutableBag $exportData, MutableBag $responseBag)
    {
        /** @var ContentRepository $repo */
        $repo = $this->em->getRepository($contentTypeName);
        $metadata = $repo->getClassMetadata();
        // Get all the records for the ContentType
        $entities = $this->query->getContent($contentTypeName);
        $contentTypeBag = $exportData->get($contentTypeName);

        foreach ($entities as $key => $entity) {
            $this->addRecord($contentTypeBag, $metadata, $entity);
        }

        /** @var MutableBag $success */
        $success = $responseBag->get('success');
        $success->add(sprintf('%s: %s records', $contentTypeName, count($entities)));
    }

    /**
     * Add a single record to the export data.
     *
     * @param MutableBag    $contentTypeBag
     * @param ClassMetadata $metadata
     * @param Content       $entity
     */
    private function addRecord(MutableBag $contentTypeBag, ClassMetadata $metadata, Content $entity)
    {
        $values = [];
        foreach ($metadata->getFieldMappings() as $field) {
            $fieldName = $field['fieldname'];
            $val = $entity->$fieldName;
            if (in_array($field['type'], ['date', 'datetime'])) {
                $val = (string) $entity->$fieldName;
            }
            if (is_callable([$val, 'serialize'])) {
                /** @var Entity $val */
                $val = $val->serialize();
            }
            $values[$fieldName] = $val;
        }

        unset($values['id']);
        $values['_id'] = $entity->getContentType() . '/' . $entity->getSlug();
        $contentTypeBag->add($values);
    }
}
