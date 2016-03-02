<?php

namespace Bolt\Tests;

use Bolt\AccessControl\PermissionParser;

class PermissionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider lexProvider
     */
    public function testLex($input, $expected)
    {
        $parser = new PermissionParser();
        $actual_ = $parser->lex($input);
        $actual = [];
        foreach ($actual_ as $a) {
            $actual[] = [
                'type'    => $a['type'],
                'capture' => $a['capture'],
            ];
        }
        $this->assertEquals($expected, $actual);
    }

    public static function lexProvider()
    {
        return [
            // A regular "word"
            [
                'this',
                [
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'this'],
                ],
            ],
            // Words follow a certain pattern
            [
                'this:is:also:valid',
                [
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'this:is:also:valid'],
                ],
            ],
            [
                'so-is:this:6',
                [
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'so-is:this:6'],
                ],
            ],
            // Properly end words
            [
                'this)',
                [
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'this'],
                    ['type' => PermissionParser::T_CLOSE_PARENS, 'capture' => null],
                ],
            ],
            [
                'this||',
                [
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'this'],
                    ['type' => PermissionParser::T_OR, 'capture' => null],
                ],
            ],
            // Do not mistake this for "and"
            [
                'andz',
                [
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'andz'],
                ],
            ],
            // Variations of the "true" token
            [
                'true',
                [
                    ['type' => PermissionParser::T_TRUE, 'capture' => null],
                ],
            ],
            [
                'TRUE',
                [
                    ['type' => PermissionParser::T_TRUE, 'capture' => null],
                ],
            ],
            [
                'True',
                [
                    ['type' => PermissionParser::T_TRUE, 'capture' => null],
                ],
            ],
            [
                'trUe',
                [
                    ['type' => PermissionParser::T_TRUE, 'capture' => null],
                ],
            ],
            // Variations of the "false" token
            [
                'false',
                [
                    ['type' => PermissionParser::T_FALSE, 'capture' => null],
                ],
            ],
            [
                'FALSE',
                [
                    ['type' => PermissionParser::T_FALSE, 'capture' => null],
                ],
            ],
            [
                'False',
                [
                    ['type' => PermissionParser::T_FALSE, 'capture' => null],
                ],
            ],
            [
                'fAlsE',
                [
                    ['type' => PermissionParser::T_FALSE, 'capture' => null],
                ],
            ],
            // Variations of the "and" token
            [
                'and',
                [
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                ],
            ],
            [
                'aNd',
                [
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                ],
            ],
            [
                '&',
                [
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                ],
            ],
            // also skip over spaces
            [
                '&& ',
                [
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                ],
            ],
            // but treat space-separated tokens separately
            [
                '& &',
                [
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                ],
            ],
            // variations of "or"
            [
                'or',
                [
                    ['type' => PermissionParser::T_OR, 'capture' => null],
                ],
            ],
            [
                '|',
                [
                    ['type' => PermissionParser::T_OR, 'capture' => null],
                ],
            ],
            [
                ' ||',
                [
                    ['type' => PermissionParser::T_OR, 'capture' => null],
                ],
            ],
            // combined "and" & "or"
            [
                '&||',
                [
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                    ['type' => PermissionParser::T_OR, 'capture' => null],
                ],
            ],
            // parens
            [
                '(',
                [
                    ['type' => PermissionParser::T_OPEN_PARENS, 'capture' => null],
                ],
            ],
            [
                ')',
                [
                    ['type' => PermissionParser::T_CLOSE_PARENS, 'capture' => null],
                ],
            ],
            // something complex, putting it all together
            [
                'this or (that and something&else ))',
                [
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'this'],
                    ['type' => PermissionParser::T_OR, 'capture' => null],
                    ['type' => PermissionParser::T_OPEN_PARENS, 'capture' => null],
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'that'],
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'something'],
                    ['type' => PermissionParser::T_AND, 'capture' => null],
                    ['type' => PermissionParser::T_QUERY, 'capture' => 'else'],
                    ['type' => PermissionParser::T_CLOSE_PARENS, 'capture' => null],
                    ['type' => PermissionParser::T_CLOSE_PARENS, 'capture' => null],
                ],
            ],
        ];
    }

    /**
     * @dataProvider runProvider
     */
    public function testRun($input, $expected)
    {
        $parser = new PermissionParser();
        $actual = $parser->run($input);
        $this->assertEquals($expected, $actual);
    }

    public static function runProvider()
    {
        return [
            // A single query
            [
                'this',
                ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
            ],
            // Parentheses around a single query should not make a difference.
            [
                '(this)',
                ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
            ],
            [
                '(((this)))',
                ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
            ],
            // Neither should whitespace.
            [
                '(((    this) ))',
                ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
            ],
            // OR query
            [
                'this or that',
                ['type' => PermissionParser::P_OR, 'value' =>
                    [
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'that'],
                    ],
                ],
            ],
            // AND query
            [
                'this and that',
                ['type' => PermissionParser::P_AND, 'value' =>
                    [
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'that'],
                    ],
                ],
            ],
            // sequences of "ands" should collapse into one
            [
                'this and that and something',
                ['type' => PermissionParser::P_AND, 'value' =>
                    [
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'that'],
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'something'],
                    ],
                ],
            ],
            // combined AND/OR query with precedence ("or" binds tighter than "and")
            [
                'this and that or something',
                ['type' => PermissionParser::P_AND, 'value' =>
                    [
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
                        ['type' => PermissionParser::P_OR, 'value' =>
                            [
                                ['type' => PermissionParser::P_SIMPLE, 'value' => 'that'],
                                ['type' => PermissionParser::P_SIMPLE, 'value' => 'something'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'this or that and something',
                ['type' => PermissionParser::P_AND, 'value' =>
                    [
                        ['type' => PermissionParser::P_OR, 'value' =>
                            [
                                ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
                                ['type' => PermissionParser::P_SIMPLE, 'value' => 'that'],
                            ],
                        ],
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'something'],
                    ],
                ],
            ],
            // parentheses to override precedence explicitly
            [
                'this or (that and something)',
                ['type' => PermissionParser::P_OR, 'value' =>
                    [
                        ['type' => PermissionParser::P_SIMPLE, 'value' => 'this'],
                        ['type' => PermissionParser::P_AND, 'value' =>
                            [
                                ['type' => PermissionParser::P_SIMPLE, 'value' => 'that'],
                                ['type' => PermissionParser::P_SIMPLE, 'value' => 'something'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
