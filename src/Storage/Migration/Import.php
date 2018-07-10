<?php

namespace Bolt\Storage\Migration;

use Bolt\Collection\Bag;
use Bolt\Collection\MutableBag;
use Bolt\Storage\Collection;
use Bolt\Storage\Entity;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Field\Type\RelationType;
use Bolt\Storage\Field\Type\TaxonomyType;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Repository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use RuntimeException;

/**
 * Database records import class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Ross Riley <riley.ross@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
final class Import
{
    /** @var EntityManager */
    private $em;
    /** @var Query */
    private $query;
    /** @var Bag */
    private $contentTypes;

    /**
     * Constructor.
     *
     * @param EntityManager $em
     * @param Query         $query
     * @param Bag           $contentTypes
     */
    public function __construct(EntityManager $em, Query $query, Bag $contentTypes)
    {
        $this->em = $em;
        $this->query = $query;
        $this->contentTypes = $contentTypes;
    }

    /**
     * @param Bag        $importData
     * @param MutableBag $responseBag
     * @param bool       $overwrite
     * @param Bag        $importUsers
     *
     * @throws \Bolt\Exception\InvalidRepositoryException
     *
     * @return MutableBag
     */
    public function run(Bag $importData, MutableBag $responseBag, $overwrite = false, Bag $importUsers = null)
    {
        $this->validateContentTypes($importData);
        $relationQueue = MutableBag::from([]);
        if ($importUsers) {
            $this->importUsers($importUsers, $responseBag);
        }

        foreach ($importData as $contentTypeName => $recordsData) {
            $this->importContentType($contentTypeName, $recordsData, $relationQueue, $responseBag, $overwrite);
        }
        $this->processRelationQueue($relationQueue);

        return $responseBag;
    }

    /**
     * @param Bag        $importUsers
     * @param MutableBag $responseBag
     *
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    private function importUsers(Bag $importUsers, MutableBag $responseBag)
    {
        /** @var Repository\UsersRepository $repo */
        $repo = $this->em->getRepository(Entity\Users::class);

        foreach ($importUsers as $user) {
            $entity = new Entity\Users($user->toArrayRecursive());
            $entity->setId(null);
            try {
                $repo->save($entity);
                $responseBag->get('success')->add(sprintf('Added user "%s".', $entity->getUsername()));
            } catch (UniqueConstraintViolationException $e) {
                $responseBag->get('warning')->add(sprintf('Skipping user "%s" as it already exists.', $entity->getUsername()));
            }
        }
    }

    /**
     * Perform an import for records under a single ContentType key.
     *
     * @param string     $contentTypeName
     * @param Bag        $importData
     * @param MutableBag $relationQueue
     * @param MutableBag $responseBag
     * @param bool       $overwrite
     *
     * @throws \Bolt\Exception\InvalidRepositoryException
     */
    private function importContentType(
        $contentTypeName,
        Bag $importData,
        MutableBag $relationQueue,
        MutableBag $responseBag,
        $overwrite
    ) {
        $count = 0;
        /** @var Repository $repo */
        $repo = $this->em->getRepository($contentTypeName);

        // Build a list of the relationship field names for this ContentType
        $relationFields = MutableBag::from([]);
        $taxonomyFields = MutableBag::from([]);
        foreach ($repo->getClassMetadata()->getFieldMappings() as $field) {
            if (is_a($field['fieldtype'], RelationType::class, true)) {
                $relationFields->add($field['fieldname']);
            }
            if (is_a($field['fieldtype'], TaxonomyType::class, true)) {
                $taxonomyFields->add($field['fieldname']);
            }
        }

        /** @var MutableBag $importDatum */
        foreach ($importData as $importDatum) {
            /** @var Content $entity */
            $entity = $repo->create(['contenttype' => $contentTypeName]);
            $values = $importDatum->toArrayRecursive();
            $entity->setValues($values);
            // Add relations now so they can still be applied if required on re-runs
            $this->addRelations($repo, $relationQueue, $importDatum, $relationFields);
            // Add taxonomy fields
            $this->addTaxonomy($entity, $taxonomyFields, $importDatum);

            $existing = $this->query->getContent($entity->getContenttype() . '/' . $entity->getSlug());
            if ($existing instanceof Content) {
                $entity->setId($existing->getId());
            }
            // If the entity already exists and we're not doing overwrite, exit early
            if ($entity->getId() !== null && $overwrite === false) {
                $responseBag->get('warning')->add(sprintf('ContentType "%s" with slug "%s" exists already! Skipping record.', $contentTypeName, $entity->getSlug()));
                continue;
            }

            $repo->save($entity);
            ++$count;
        }

        $responseBag->get('success')->add("- $count records for '$contentTypeName'");
    }

    /**
     * @param Content $entity
     * @param Bag     $taxonomyFields
     * @param Bag     $importDatum
     */
    private function addTaxonomy(Content $entity, Bag $taxonomyFields, Bag $importDatum)
    {
        /** @var Collection\Taxonomy $taxonomies */
        $taxonomies = $this->em->createCollection(Entity\Taxonomy::class);
        $taxonomy = [];
        foreach ($taxonomyFields as $taxonomyField) {
            if ($importDatum->get($taxonomyField) === null) {
                continue;
            }
            foreach ($importDatum->get($taxonomyField) as $value) {
                $taxonomy[$taxonomyField][] = $value->toArray();
                $entity->set($taxonomyField, null);
            }
        }

        $taxonomies->setFromPost(['taxonomy' => $taxonomy], $entity);
        $entity->setTaxonomy($taxonomies);
    }

    /**
     * Queue an entities relations for later processing.
     *
     * @param Repository $repo
     * @param MutableBag $relationQueue
     * @param Bag        $importDatum
     * @param MutableBag $relationFields
     */
    private function addRelations(Repository $repo, MutableBag $relationQueue, Bag $importDatum, MutableBag $relationFields)
    {
        $classMeta = $repo->getClassMetadata();
        foreach ($relationFields as $fieldName) {
            $relationName = $classMeta->getBoltName() . '/' . $importDatum->get('slug');

            $existing = (array) $relationQueue->get($relationName);
            $relatedEntities = $importDatum->get($fieldName);

            if ($relatedEntities) {
                $relationQueue->set($relationName, array_merge($relatedEntities->toArray(), $existing));
            }
        }
    }

    /**
     * Since relations can't be processed until we are sure all the individual
     * records are saved this goes through the queue after an import and links
     * up all the related ones.
     *
     * @param Bag $relationQueue
     */
    private function processRelationQueue(Bag $relationQueue)
    {
        foreach ($relationQueue as $source => $links) {
            $entity = $this->query->getContent($source);
            $relations = [];
            foreach ($links as $linkKey) {
                $relation = $this->query->getContent($linkKey);
                $relatedKey = (string) $relation->getContentType();

                $relations[$relatedKey][] = $relation->getId();
            }
            /** @var Collection\Relations $related */
            $related = $this->em->createCollection(Entity\Relations::class);
            $related->setFromPost(['relation' => $relations], $entity);
            $entity->setRelation($related);
            $this->em->save($entity);
        }
    }

    /**
     * Check that the import file's ContentTypes exists.
     *
     * @param Bag $importData
     *
     * @throws RuntimeException
     */
    private function validateContentTypes(Bag $importData)
    {
        $contentTypeNames = $this->contentTypes->keys()->toArray();
        $importData->filter(function ($k, $v) use ($contentTypeNames) {
            if (!in_array($k, $contentTypeNames)) {
                throw new RuntimeException(sprintf('ContentType "%s" is not configured.', $k));
            }
        });
    }
}
