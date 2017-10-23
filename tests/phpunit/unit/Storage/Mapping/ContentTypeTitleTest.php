<?php

namespace Bolt\Tests\Storage\Mapping;

use Bolt\Tests\Storage\Mapping\Mock\ContentTypeTitleMock;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Storage\Mapping\ContentTypeTitleTrait
 *
 * Bob den Otter <bobdenotter@gmail.com>
 */
class ContentTypeTitleTest extends TestCase
{
    /**
     * @return array
     */
    public function providerNames()
    {
        return [
            // EN
            'en with title' => [
                ['title'], ['fields' => ['title' => 'koala']],
            ],
            'en with name' => [
                ['name'], ['fields' => ['name' => 'koala']],
            ],
            'en with caption' => [
                ['caption'], ['fields' => ['caption' => 'koala']],
            ],
            'en with subject' => [
                ['subject'], ['fields' => ['subject' => 'koala']],
            ],

            // NL
            'nl with titel' => [
                ['titel'], ['fields' => ['titel' => 'koala']],
            ],
            'nl with naam' => [
                ['naam'], ['fields' => ['naam' => 'koala']],
            ],
            'nl with onderwerp' => [
                ['onderwerp'], ['fields' => ['onderwerp' => 'koala']],
            ],

            // FR
            'fr with nom' => [
                ['nom'], ['fields' => ['nom' => 'koala']],
            ],
            'fr with sujet' => [
                ['sujet'], ['fields' => ['sujet' => 'koala']],
            ],

            // ES
            'es with nombre' => [
                ['nombre'], ['fields' => ['nombre' => 'koala']],
            ],
            'es with sujeto' => [
                ['sujeto'], ['fields' => ['sujeto' => 'koala']],
            ],

            // PT
            'pt with titulo' => [
                ['titulo'], ['fields' => ['titulo' => 'koala']],
            ],
            'pt with nome' => [
                ['nome'], ['fields' => ['nome' => 'koala']],
            ],
            'pt with subtitulo' => [
                ['subtitulo'], ['fields' => ['subtitulo' => 'koala']],
            ],
            'pt with assunto' => [
                ['assunto'], ['fields' => ['assunto' => 'koala']],
            ],

            'title_format singular' => [
                ['koala'], ['title_format' => 'koala'],
            ],
            'title_format plural' => [
                ['koala', 'dropbear'], ['title_format' => ['koala', 'dropbear']],
            ],
        ];
    }

    /**
     * @dataProvider providerNames
     *
     * @param array $expect
     * @param array $contentType
     */
    public function testGetTitleColumnName(array $expect, array $contentType)
    {
        $mock = new ContentTypeTitleMock();
        $result = $mock->getName($contentType);

        self::assertSame(reset($expect), $result);
    }

    /**
     * @dataProvider providerNames
     *
     * @param array $expect
     * @param array $contentType
     */
    public function testGetTitleColumnNames(array $expect, array $contentType)
    {
        $mock = new ContentTypeTitleMock();
        $result = $mock->getNames($contentType);

        self::assertSame($expect, $result);
    }

    public function testFirstTextField()
    {
        $contentType = [
            'fields' => [
                'wysiwyg'  => ['type' => 'html'],
                'youtube'  => ['type' => 'video'],
                'dropbear' => ['type' => 'textarea'],
                'koala'    => ['type' => 'text'],
            ],
        ];

        $mock = new ContentTypeTitleMock();
        $result = $mock->getNames($contentType);

        self::assertSame(['koala'], $result);
    }

    public function testTitleFormatOne()
    {
        $contentType = [
            'fields' => [
                'wysiwyg'  => ['type' => 'html'],
                'youtube'  => ['type' => 'video'],
                'dropbear' => ['type' => 'textarea'],
                'koala'    => ['type' => 'text'],
            ],
            'title_format' => ['dropbear'],
        ];

        $mock = new ContentTypeTitleMock();
        $result = $mock->getNames($contentType);

        self::assertSame(['dropbear'], $result);
    }

    public function testTitleFormatMultiple()
    {
        $contentType = [
            'fields' => [
                'wysiwyg'  => ['type' => 'html'],
                'youtube'  => ['type' => 'video'],
                'dropbear' => ['type' => 'textarea'],
                'koala'    => ['type' => 'text'],
            ],
            'title_format' => ['koala', 'dropbear'],
        ];

        $mock = new ContentTypeTitleMock();
        $result = $mock->getNames($contentType);

        self::assertSame(['koala', 'dropbear'], $result);
    }

    public function testOnlyFirstTextField()
    {
        $contentType = [
            'fields' => [
                'wysiwyg'  => ['type' => 'html'],
                'youtube'  => ['type' => 'video'],
                'dropbear' => ['type' => 'text'],
                'koala'    => ['type' => 'text'],
            ],
        ];

        $mock = new ContentTypeTitleMock();
        $result = $mock->getNames($contentType);

        self::assertSame(['dropbear'], $result);
    }

    public function testNoTextFields()
    {
        $contentType = [
            'fields' => [
                'wysiwyg'  => ['type' => 'html'],
                'youtube'  => ['type' => 'video'],
                'dropbear' => ['type' => 'textarea'],
                'koala'    => ['type' => 'block'],
            ],
        ];

        $mock = new ContentTypeTitleMock();
        $result = $mock->getNames($contentType);

        self::assertSame([], $result);
    }
}
