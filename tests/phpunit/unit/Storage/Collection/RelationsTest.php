<?php

namespace Bolt\Tests\Storage\Collection;

use Bolt\Storage\Collection;
use Bolt\Storage\Entity\Content;
use Bolt\Tests\BoltUnitTest;

class RelationsTest extends BoltUnitTest
{
    public function setUp()
    {
        $this->addSomeContent(['pages', 'entries', 'showcases']);
    }

    public function testGetField()
    {
        $app = $this->getApp();
        /** @var Content $owner */
        $owner = $app['query']->getContent('showcases/1');
        $relations = $owner->getRelation();

        $this->assertInstanceOf(Collection\Relations::class, $relations);
        $field = $relations->getField('empty');
        $this->assertInstanceOf(Collection\Relations::class, $field);
        $this->assertCount(0, $field);

        $relations->associate('pages', [1]);
        $relations->associate('entries', [2]);
        $this->assertCount(2, $relations);
        $this->assertCount(1, $relations->getField('pages'));
        $this->assertCount(1, $relations->getField('entries'));
    }

    public function testAssociate()
    {
        $app = $this->getApp();
        /** @var Content $owner */
        $owner = $app['query']->getContent('showcases/1');
        $relations = $owner->getRelation();
        $entityToTest = $app['query']->getContent('pages/1');

        $relations->associate($entityToTest);
        $this->assertCount(1, $relations['pages']);
    }

    public function testAssociateCollection()
    {
        $app = $this->getApp();
        /** @var Content $owner */
        $owner = $app['query']->getContent('showcases/1');
        $relations = $owner->getRelation();
        $collectionToTest = $app['query']->getContent('pages');

        $relations->associate($collectionToTest);
        $this->assertCount(count($collectionToTest), $relations['pages']);
    }
}
