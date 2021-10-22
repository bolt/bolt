<?php

namespace Bolt\Storage\Migration;

use Bolt\Collection\Bag;
use Bolt\Collection\MutableBag;
use Bolt\Common\Str;
use Bolt\Exception\StorageException;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Entity\Entity;
use Bolt\Storage\Entity\Users;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Field\Type\RelationType;
use Bolt\Storage\Field\Type\SelectMultipleType;
use Bolt\Storage\Field\Type\SelectType;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Repository\ContentRepository;
use Bolt\Version;
use Carbon\Carbon;
use function is_array;
use function is_string;
use function sprintf;

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

    /** @var array */
    private $referenceCache = [];

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
     * @param bool       $includeUsers
     *
     * @throws \Bolt\Exception\InvalidRepositoryException
     *
     * @return MutableBag
     */
    public function run(array $exportContentTypes, MutableBag $responseBag, $includeUsers = false)
    {
        // Get initial data object
        $exportData = MutableBag::from([]);
        // Add the meta header
        $this->addExportMeta($exportData);
        if ($includeUsers) {
            $this->addExportUsers($exportData);
        }

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
     * Add the user table to the export.
     *
     * @param MutableBag $exportData
     *
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    private function addExportUsers(MutableBag $exportData)
    {
        $repo = $this->em->getRepository(Users::class);
        $users = $repo->findAll();
        $export = [];
        /** @var Entity $user */
        foreach ($users as $user) {
            $export[] = Bag::from($user->toArray())
                ->filter(function ($k) {
                    return \in_array($k, ['id', 'username', 'displayname', 'password', 'email', 'enabled', 'roles']);
                }
            );
        }
        $exportData->set('__users', $export);
    }

    /**
     * Get the records for a given ContentType.
     *
     * @param string     $contentTypeName
     * @param MutableBag $exportData
     * @param MutableBag $responseBag
     *
     * @throws \Bolt\Exception\InvalidRepositoryException
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
            if (!$entity->getSlug()) {
                throw new StorageException("Cannot export an entity that does not have a slug. Check contenttype/id {$contentTypeName}/{$entity->id} to make sure it has a slug!");
            }

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

            if ($fieldName === 'incomingrelation') {
                // There's no need to store the incoming end of a relation.
                continue;
            }

            if ($field['fieldtype'] === SelectType::class && is_string($field['data']['values']) && !empty(Str::splitFirst($field['data']['values'], '/'))) {
                $val = $this->getReferencedContent($entity, $field);
            }

            if ($field['fieldtype'] === SelectMultipleType::class && is_string($field['data']['values']) && !empty(Str::splitFirst($field['data']['values'], '/'))) {
                $val = $this->getReferencedContent($entity, $field);
            }

            if ($field['fieldtype'] === RelationType::class) {
                $val = $entity->getRelation($fieldName)
                    ->map(
                        function ($item) use ($fieldName) {
                            return "$fieldName/{$item->slug}";
                        }
                    )
                    ->getValues();
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

    private function getReferencedContent(Content $entity, array $field): array
    {
        $fieldName = $field['fieldname'];

        if (empty($entity->$fieldName)) {
            return ['value' => null];
        }

        $values = $entity->$fieldName;
        $contentTypeName = Str::splitFirst($field['data']['values'], '/');
        if (is_array($values)) {
            $val = [];
            foreach ($values as $value) {
                $reference = $contentTypeName . '/' . $value;
                $val[] = $this->getContent($reference, $contentTypeName, (string) $value);
            }
        } else {
            $reference = $contentTypeName . '/' . $entity->$fieldName;
            $val = $this->getContent($reference, $contentTypeName, (string) $entity->$fieldName);
        }

        return $val;
    }

    private function getContent(string $reference, string $contentTypeName, string $value)
    {
        if (isset($this->referenceCache[$reference])) {
            return $this->referenceCache[$reference];
        }

        $referencedContent = $this->query->getContent($contentTypeName, ['id' => $value]);

        $val = [];

        /** @var Content $r */
        foreach ($referencedContent as $r) {
            $val[] = [
                'value' => (string) $value,
                '_id' => sprintf('%s/%s', $r->getContenttype(), $r->getSlug())
            ];
        }

        $this->referenceCache[$reference] = $val;

        return $val;
    }
}
