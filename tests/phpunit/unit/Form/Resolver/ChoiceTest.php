<?php

namespace Bolt\Tests\Form\Resolver;

use Bolt\Form\Resolver\Choice;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Repository\ContentRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @covers \Bolt\Form\Resolver\Choice
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ChoiceTest extends TestCase
{
    public function testGetYamlNoSelect()
    {
        $resolver = $this->getResolver();
        $contentType = new ContentType('koala', [
            'fields' => [
                'text_field' => ['type'   => 'text'],
            ],
        ]);
        $result = $resolver->get($contentType, []);

        $this->assertNull($result);
    }

    public function testGetYamlIndexArray()
    {
        $resolver = $this->getResolver();
        $values = ['foo', 'bar', 'koala', 'drop bear'];
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                ],
            ],
        ]);
        $result = $resolver->get($contentType, []);

        $this->assertSame(['select_array' => $values], $result);
    }

    public function testGetYamlRepeaterIndexArray()
    {
        $resolver = $this->getResolver();
        $values = ['foo', 'bar', 'koala', 'drop bear'];
        $contentType = new ContentType('koala', [
            'fields' => [
                'repeater' => [
                    'type'   => 'repeater',
                    'fields' => [
                        'select_array' => [
                            'type'   => 'select',
                            'values' => $values,
                        ],
                    ],
                ]
            ],
        ]);
        $result = $resolver->get($contentType, []);

        $this->assertSame(['select_array' => $values], $result);
    }

    public function testGetYamlIndexArraySorted()
    {
        $resolver = $this->getResolver();
        $values = ['foo', 'bar', 'koala', 'drop bear'];
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'     => 'select',
                    'sortable' => true,
                    'values'   => $values,
                ],
            ],
        ]);
        $result = $resolver->get($contentType, []);
        asort($values, SORT_REGULAR);

        $this->assertSame(['select_array' => $values], $result);
    }

    public function testGetYamlIndexArrayLimit()
    {
        $resolver = $this->getResolver();
        $values = ['foo', 'bar', 'koala', 'drop bear'];
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'limit'  => 2,
                    'values' => $values,
                ],
            ],
        ]);
        $result = $resolver->get($contentType, []);

        $this->assertSame(['select_array' => array_slice($values, 0, 2)], $result);
    }

    public function testGetYamlHashArray()
    {
        $resolver = $this->getResolver();
        $values = ['foo' => 'Foo Magoo', 'bar' => 'Iron Bar', 'koala' => 'Kenny', 'drop bear' => 'Danger Danger'];
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                ],
            ],
        ]);
        $result = $resolver->get($contentType, []);

        $this->assertSame(['select_array' => $values], $result);
    }

    public function testGetYamlHashArraySorted()
    {
        $resolver = $this->getResolver();
        $values = ['foo' => 'Foo Magoo', 'bar' => 'Iron Bar', 'koala' => 'Kenny', 'drop bear' => 'Danger Danger'];
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'     => 'select',
                    'sortable' => true,
                    'values'   => $values,
                ],
            ],
        ]);
        $result = $resolver->get($contentType, []);
        asort($values, SORT_REGULAR);

        $this->assertSame(['select_array' => $values], $result);
    }

    public function testGetYamlHashArrayLimit()
    {
        $resolver = $this->getResolver();
        $values = ['foo' => 'Foo Magoo', 'bar' => 'Iron Bar', 'koala' => 'Kenny', 'drop bear' => 'Danger Danger'];
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'limit'  => 2,
                    'values' => $values,
                ],
            ],
        ]);
        $result = $resolver->get($contentType, []);

        $this->assertSame(['select_array' => array_slice($values, 0, 2)], $result);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "values" key for a ContentType select must be in the form of ContentType/field_name but "contenttype" given
     */
    public function testGetEntityInvalidQuery()
    {
        $resolver = $this->getResolver();
        $values = 'contenttype';
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                ],
            ],
        ]);
        $resolver->get($contentType, []);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "values" key for a ContentType select must include a single field, or comma separated fields, "contenttype/" given
     */
    public function testGetEntityInvalidQueryFields()
    {
        $resolver = $this->getResolver();
        $values = 'contenttype/';
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                ],
            ],
        ]);
        $resolver->get($contentType, []);
    }

    public function testGetEntitiesNotFound()
    {
        // Not passing entities into the mock will return a false inside the
        // SUT, which should become an empty array.
        $resolver = $this->getResolver();
        $values = 'contenttype/field_1';
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                ],
            ],
        ]);
        $result = $resolver->get($contentType, []);

        $this->assertSame(['select_array' => []], $result);
    }

    public function testGetEntityOneField()
    {
        $resolver = $this->getResolver($this->getEntities());
        $values = 'contenttype/field_1';
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                ],
            ],
        ]);
        $expect = [
            'select_array' => [
                10 => 'Foo Magoo',
                22 => 'Iron Bar',
                33 => 'Kenny Koala',
                42 => 'Drop Bear',
            ],
        ];
        $result = $resolver->get($contentType, []);

        $this->assertSame($expect, $result);
    }

    public function testGetEntityTwoFields()
    {
        $resolver = $this->getResolver($this->getEntities());
        $values = 'contenttype/field_1,field_2';
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                ],
            ],
        ]);
        $expect = [
            'select_array' => [
                10 => 'Foo Magoo / Magoo Foo',
                22 => 'Iron Bar / Bar Iron',
                33 => 'Kenny Koala / Koala Kenny',
                42 => 'Drop Bear / Danger Danger',
            ],
        ];
        $result = $resolver->get($contentType, []);

        $this->assertSame($expect, $result);
    }

    public function testGetEntityFiltered()
    {
        $resolver = $this->getResolver(null, $this->getEntities());
        $values = 'contenttype/field_1,field_2';
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                    'filter' => ['category' => 'test']
                ],
            ],
        ]);
        $expect = [
            'select_array' => [
                10 => 'Foo Magoo / Magoo Foo',
                22 => 'Iron Bar / Bar Iron',
                33 => 'Kenny Koala / Koala Kenny',
                42 => 'Drop Bear / Danger Danger',
            ],
        ];
        $result = $resolver->get($contentType, []);

        $this->assertSame($expect, $result);
    }

    public function testGetEntityKeys()
    {
        $resolver = $this->getResolver(null, $this->getEntities());
        $values = 'contenttype/field_1';
        $contentType = new ContentType('koala', [
            'fields' => [
                'select_array' => [
                    'type'   => 'select',
                    'values' => $values,
                    'keys'   => 'field_2',
                    'filter' => ['category' => 'test']
                ],
            ],
        ]);
        $expect = [
            'select_array' => [
                'Magoo Foo' => 'Foo Magoo',
                'Bar Iron' => 'Iron Bar',
                'Koala Kenny' => 'Kenny Koala',
                'Danger Danger' => 'Drop Bear',
            ],
        ];
        $result = $resolver->get($contentType, []);

        $this->assertSame($expect, $result);
    }


    public function testGetTemplateFieldsSelect()
    {
        $resolver = $this->getResolver();
        $contentType = new ContentType('koala', ['fields' => []]);
        $values = ['foo', 'bar', 'koala', 'drop bear'];
        $templateFields = [
            'select_array' => [
                'type'   => 'select',
                'values' => $values,
            ],
        ];
        $result = $resolver->get($contentType, $templateFields);

        $this->assertSame(['templatefields' => ['select_array' => $values]], $result);
    }

    /**
     * @return Content[]
     */
    private function getEntities()
    {
        return [
            new Content(['id' => 10, 'field_1' => 'Foo Magoo', 'field_2' => 'Magoo Foo']),
            new Content(['id' => 22, 'field_1' => 'Iron Bar', 'field_2' => 'Bar Iron']),
            new Content(['id' => 33, 'field_1' => 'Kenny Koala', 'field_2' => 'Koala Kenny']),
            new Content(['id' => 42, 'field_1' => 'Drop Bear', 'field_2' => 'Danger Danger']),
        ];
    }

    /**
     * @param array $repoReturn
     * @param array $queryReturn
     *
     * @return Choice
     */
    private function getResolver($repoReturn = null, $queryReturn = null)
    {
        /** @var ContentRepository|MockObject $mockRepo */
        $mockRepo = $this->getMockBuilder(ContentRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['findBy'])
            ->getMock()
        ;
        if ($repoReturn) {
            $mockRepo
                ->expects($this->atLeastOnce())
                ->method('findBy')
                ->willReturn($repoReturn)
            ;
        }
        /** @var EntityManager|MockObject $mockEntityManager */
        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRepository'])
            ->getMock()
        ;
        $mockEntityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($mockRepo)
        ;
        /** @var Query|MockObject $mockQuery */
        $mockQuery = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock()
        ;
        if ($queryReturn) {
            $mockQuery
                ->expects($this->atLeastOnce())
                ->method('getContent')
                ->willReturn($queryReturn)
            ;
        }

        return new Choice($mockEntityManager, $mockQuery);
    }
}
