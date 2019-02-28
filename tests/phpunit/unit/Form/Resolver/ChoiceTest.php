<?php

namespace Bolt\Tests\Form\Resolver;

use Bolt\Form\Resolver\Choice;
use Bolt\Storage\Entity\Content;
use Bolt\Storage\Mapping\ContentType;
use Bolt\Storage\Query\Query;
use Bolt\Storage\Query\QueryResultset;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * @covers \Bolt\Form\Resolver\Choice
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ChoiceTest extends TestCase
{
    public function providerGetYaml()
    {
        return [
            'No select fields present' => [
                null,
                ['text_field' => ['type' => 'text']],
            ],
            'Field with indexed array' => [
                ['select_array' => ['foo', 'bar', 'koala', 'drop bear']],
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => ['foo', 'bar', 'koala', 'drop bear'],
                    ],
                ],
            ],
            'Repeater field with indexed array' => [
                [
                    'repeater' => [
                        'select_array' => ['foo', 'bar', 'koala', 'drop bear']
                    ],
                ],
                [
                    'repeater' => [
                        'type'   => 'repeater',
                        'fields' => [
                            'select_array' => [
                                'type'   => 'select',
                                'values' => ['foo', 'bar', 'koala', 'drop bear'],
                            ],
                        ],
                    ],
                ],
            ],
            'Field with sorted indexed array' => [
                ['select_array' => [1 => 'bar', 3 => 'drop bear', 0 => 'foo', 2 => 'koala']],
                [
                    'select_array' => [
                        'type'     => 'select',
                        'sortable' => true,
                        'values'   => ['foo', 'bar', 'koala', 'drop bear'],
                    ],
                ],
            ],
            'Field un-sorted indexed array' => [
                ['select_array' => [1 => 'bar', 3 => 'drop bear', 0 => 'foo', 2 => 'koala']],
                [
                    'select_array' => [
                        'type'     => 'select',
                        'sortable' => false,
                        'values'   => [1 => 'bar', 3 => 'drop bear', 0 => 'foo', 2 => 'koala'],
                    ],
                ],
            ],
            'Field with limited count indexed array' => [
                ['select_array' => ['foo', 'bar']],
                [
                    'select_array' => [
                        'type'   => 'select',
                        'limit'  => 2,
                        'values' => ['foo', 'bar', 'koala', 'drop bear'],
                    ],
                ],
            ],
            'Field with hashed array' => [
                ['select_array' => ['foo' => 'Foo Magoo', 'bar' => 'Iron Bar', 'koala' => 'Kenny', 'drop bear' => 'Danger Danger']],
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => ['foo' => 'Foo Magoo', 'bar' => 'Iron Bar', 'koala' => 'Kenny', 'drop bear' => 'Danger Danger'],
                    ],
                ],
            ],
            'Field with sorted hashed array' => [
                ['select_array' => ['drop bear' => 'Danger Danger', 'foo' => 'Foo Magoo', 'bar' => 'Iron Bar', 'koala' => 'Kenny']],
                [
                    'select_array' => [
                        'type'     => 'select',
                        'sortable' => true,
                        'values'   => ['foo' => 'Foo Magoo', 'bar' => 'Iron Bar', 'koala' => 'Kenny', 'drop bear' => 'Danger Danger'],
                    ],
                ],
            ],
            'Field with limited count hashed array' => [
                ['select_array' => ['foo' => 'Foo Magoo', 'bar' => 'Iron Bar']],
                [
                    'select_array' => [
                        'type'   => 'select',
                        'limit'  => 2,
                        'values' => ['foo' => 'Foo Magoo', 'bar' => 'Iron Bar', 'koala' => 'Kenny', 'drop bear' => 'Danger Danger'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerGetYaml
     *
     * @param mixed $expected
     * @param array $fields
     */
    public function testGetYaml($expected, array $fields)
    {
        $resolver = $this->getResolver();
        $contentType = new ContentType('koala', ['fields' => $fields]);

        $result = $resolver->get($contentType, []);

        $this->assertSame($expected, $result);
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

    public function providerGetEntities()
    {
        $entities = $this->getEntities();

        return [
            'No entities found' => [
                // Not passing entities into the mock will return a false inside the
                // SUT, which should become an empty array.
                ['select_array' => []],
                null,
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => 'contenttype/field_1',
                    ],
                ],
            ],
            'Single ContentType field' => [
                [
                    'select_array' => [
                        10 => 'Foo Magoo',
                        22 => 'Iron Bar',
                        33 => 'Kenny Koala',
                        42 => 'Drop Bear',
                    ],
                ],
                $entities,
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => 'contenttype/field_1',
                    ],
                ],
            ],
            'Two ContentType fields' => [
                [
                    'select_array' => [
                        10 => 'Foo Magoo / Magoo Foo',
                        22 => 'Iron Bar / Bar Iron',
                        33 => 'Kenny Koala / Koala Kenny',
                        42 => 'Drop Bear / Danger Danger',
                    ],
                ],
                $entities,
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => 'contenttype/field_1,field_2',
                    ],
                ],
            ],
            'Two ContentType fields with filter' => [
                [
                    'select_array' => [
                        10 => 'Foo Magoo / Magoo Foo',
                        22 => 'Iron Bar / Bar Iron',
                        33 => 'Kenny Koala / Koala Kenny',
                        42 => 'Drop Bear / Danger Danger',
                    ],
                ],
                $entities,
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => 'contenttype/field_1,field_2',
                        'filter' => ['category' => 'test'],
                    ],
                ],
            ],
            'Single ContentType field with different keys' => [
                [
                    'select_array' => [
                        'Magoo Foo'     => 'Foo Magoo',
                        'Bar Iron'      => 'Iron Bar',
                        'Koala Kenny'   => 'Kenny Koala',
                        'Danger Danger' => 'Drop Bear',
                    ],
                ],
                $entities,
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => 'contenttype/field_1',
                        'keys'   => 'field_2',
                        'filter' => ['category' => 'test'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerGetEntities
     *
     * @param array $expected
     * @param array $mockReturn
     * @param array $fields
     */
    public function testGetEntities(array $expected, $mockReturn, array $fields)
    {
        $resolver = $this->getResolver($mockReturn);
        $contentType = new ContentType('koala', ['fields' => $fields]);

        $result = $resolver->get($contentType, []);

        $this->assertSame($expected, $result);
    }

    public function providerEntitySorted()
    {
        return [
            'Sorted normal' => [
                'field_1',
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => 'contenttype/field_1,field_2',
                        'sort'   => 'field_1',
                    ],
                ],
            ],
            'Sorted reverse' => [
                '-field_2',
                [
                    'select_array' => [
                        'type'   => 'select',
                        'values' => 'contenttype/field_1,field_2',
                        'sort'   => '-field_2',
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider providerEntitySorted
     *
     * @param string $expected
     * @param array  $fields
     */
    public function testGetEntitySorted($expected, array $fields)
    {
        $contentType = new ContentType('koala', ['fields' => $fields]);

        /** @var Query|MockObject $mockQuery */
        $mockQuery = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock()
        ;
        $mockQuery
            ->expects($this->at(0))
            ->method('getContent')
            ->with('contenttype', ['order' => $expected, 'limit' => Choice::DEFAULT_LIMIT])
        ;

        $resolver = new Choice($mockQuery);

        $resolver->get($contentType, []);
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
     * @return QueryResultset
     */
    private function getEntities()
    {
        $set = new QueryResultset();

        $results = [
            new Content(['id' => 10, 'field_1' => 'Foo Magoo', 'field_2' => 'Magoo Foo']),
            new Content(['id' => 22, 'field_1' => 'Iron Bar', 'field_2' => 'Bar Iron']),
            new Content(['id' => 33, 'field_1' => 'Kenny Koala', 'field_2' => 'Koala Kenny']),
            new Content(['id' => 42, 'field_1' => 'Drop Bear', 'field_2' => 'Danger Danger']),
        ];
        $set->add($results, 'pages');

        return $set;
    }

    /**
     * @param array $mockReturn
     *
     * @return Choice
     */
    private function getResolver($mockReturn = null)
    {
        /** @var Query|MockObject $mockQuery */
        $mockQuery = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->setMethods(['getContent'])
            ->getMock()
        ;
        if ($mockReturn) {
            $mockQuery
                ->expects($this->atLeastOnce())
                ->method('getContent')
                ->willReturn($mockReturn)
            ;
        }

        return new Choice($mockQuery);
    }
}
