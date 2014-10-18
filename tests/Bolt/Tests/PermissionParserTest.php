<?php

namespace Bolt\Tests;

use Bolt\PermissionParser;

class PermissionParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider lexProvider
     */
    public function testLex($input, $expected)
    {
        $parser = new PermissionParser();
        $actual_ = $parser->lex($input);
        $actual = array();
        foreach ($actual_ as $a) {
            $actual[] = array(
                'type' => $a['type'],
                'capture' => $a['capture']
            );
        }
        $this->assertEquals($expected, $actual);
    }

    public static function lexProvider()
    {
        return array(
            // A regular "word"
            array(
                "this",
                array(
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'this')
                )
            ),
            // Words follow a certain pattern
            array(
                "this:is:also:valid",
                array(
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'this:is:also:valid')
                )
            ),
            array(
                "so-is:this:6",
                array(
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'so-is:this:6')
                )
            ),
            // Properly end words
            array(
                "this)",
                array(
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'this'),
                    array('type' => PermissionParser::T_CLOSE_PARENS, 'capture' => null)
                )
            ),
            array(
                "this||",
                array(
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'this'),
                    array('type' => PermissionParser::T_OR, 'capture' => null)
                )
            ),
            // Do not mistake this for "and"
            array(
                "andz",
                array(
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'andz')
                )
            ),
            // Variations of the "true" token
            array(
                "true",
                array(
                    array('type' => PermissionParser::T_TRUE, 'capture' => null)
                )
            ),
            array(
                "TRUE",
                array(
                    array('type' => PermissionParser::T_TRUE, 'capture' => null)
                )
            ),
            array(
                "True",
                array(
                    array('type' => PermissionParser::T_TRUE, 'capture' => null)
                )
            ),
            array(
                "trUe",
                array(
                    array('type' => PermissionParser::T_TRUE, 'capture' => null)
                )
            ),
            // Variations of the "false" token
            array(
                "false",
                array(
                    array('type' => PermissionParser::T_FALSE, 'capture' => null)
                )
            ),
            array(
                "FALSE",
                array(
                    array('type' => PermissionParser::T_FALSE, 'capture' => null)
                )
            ),
            array(
                "False",
                array(
                    array('type' => PermissionParser::T_FALSE, 'capture' => null)
                )
            ),
            array(
                "fAlsE",
                array(
                    array('type' => PermissionParser::T_FALSE, 'capture' => null)
                )
            ),
            // Variations of the "and" token
            array(
                "and",
                array(
                    array('type' => PermissionParser::T_AND, 'capture' => null)
                )
            ),
            array(
                "aNd",
                array(
                    array('type' => PermissionParser::T_AND, 'capture' => null)
                )
            ),
            array(
                "&",
                array(
                    array('type' => PermissionParser::T_AND, 'capture' => null)
                )
            ),
            // also skip over spaces
            array(
                "&& ",
                array(
                    array('type' => PermissionParser::T_AND, 'capture' => null)
                )
            ),
            // but treat space-separated tokens separately
            array(
                "& &",
                array(
                    array('type' => PermissionParser::T_AND, 'capture' => null),
                    array('type' => PermissionParser::T_AND, 'capture' => null)
                )
            ),
            // variations of "or"
            array(
                "or",
                array(
                    array('type' => PermissionParser::T_OR, 'capture' => null)
                )
            ),
            array(
                "|",
                array(
                    array('type' => PermissionParser::T_OR, 'capture' => null)
                )
            ),
            array(
                " ||",
                array(
                    array('type' => PermissionParser::T_OR, 'capture' => null)
                )
            ),
            // combined "and" & "or"
            array(
                "&||",
                array(
                    array('type' => PermissionParser::T_AND, 'capture' => null),
                    array('type' => PermissionParser::T_OR, 'capture' => null)
                )
            ),
            // parens
            array(
                "(",
                array(
                    array('type' => PermissionParser::T_OPEN_PARENS, 'capture' => null)
                )
            ),
            array(
                ")",
                array(
                    array('type' => PermissionParser::T_CLOSE_PARENS, 'capture' => null)
                )
            ),
            // something complex, putting it all together
            array(
                "this or (that and something&else ))",
                array(
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'this'),
                    array('type' => PermissionParser::T_OR, 'capture' => null),
                    array('type' => PermissionParser::T_OPEN_PARENS, 'capture' => null),
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'that'),
                    array('type' => PermissionParser::T_AND, 'capture' => null),
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'something'),
                    array('type' => PermissionParser::T_AND, 'capture' => null),
                    array('type' => PermissionParser::T_QUERY, 'capture' => 'else'),
                    array('type' => PermissionParser::T_CLOSE_PARENS, 'capture' => null),
                    array('type' => PermissionParser::T_CLOSE_PARENS, 'capture' => null),
                )
            ),
        );
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
        return array(
            // A single query
            array(
                "this",
                array('type' => PermissionParser::P_SIMPLE, 'value' => 'this')
            ),
            // Parentheses around a single query should not make a difference.
            array(
                "(this)",
                array('type' => PermissionParser::P_SIMPLE, 'value' => 'this')
            ),
            array(
                "(((this)))",
                array('type' => PermissionParser::P_SIMPLE, 'value' => 'this')
            ),
            // Neither should whitespace.
            array(
                "(((    this) ))",
                array('type' => PermissionParser::P_SIMPLE, 'value' => 'this')
            ),
            // OR query
            array(
                "this or that",
                array('type' => PermissionParser::P_OR, 'value' =>
                    array(
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'this'),
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'that'),
                    )
                )
            ),
            // AND query
            array(
                "this and that",
                array('type' => PermissionParser::P_AND, 'value' =>
                    array(
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'this'),
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'that'),
                    )
                )
            ),
            // sequences of "ands" should collapse into one
            array(
                "this and that and something",
                array('type' => PermissionParser::P_AND, 'value' =>
                    array(
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'this'),
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'that'),
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'something'),
                    )
                )
            ),
            // combined AND/OR query with precedence ("or" binds tighter than "and")
            array(
                "this and that or something",
                array('type' => PermissionParser::P_AND, 'value' =>
                    array(
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'this'),
                        array('type' => PermissionParser::P_OR, 'value' =>
                            array(
                                array('type' => PermissionParser::P_SIMPLE, 'value' => 'that'),
                                array('type' => PermissionParser::P_SIMPLE, 'value' => 'something'),
                            )
                        ),
                    )
                )
            ),
            array(
                "this or that and something",
                array('type' => PermissionParser::P_AND, 'value' =>
                    array(
                        array('type' => PermissionParser::P_OR, 'value' =>
                            array(
                                array('type' => PermissionParser::P_SIMPLE, 'value' => 'this'),
                                array('type' => PermissionParser::P_SIMPLE, 'value' => 'that'),
                            )
                        ),
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'something'),
                    )
                )
            ),
            // parentheses to override precedence explicitly
            array(
                "this or (that and something)",
                array('type' => PermissionParser::P_OR, 'value' =>
                    array(
                        array('type' => PermissionParser::P_SIMPLE, 'value' => 'this'),
                        array('type' => PermissionParser::P_AND, 'value' =>
                            array(
                                array('type' => PermissionParser::P_SIMPLE, 'value' => 'that'),
                                array('type' => PermissionParser::P_SIMPLE, 'value' => 'something'),
                            )
                        ),
                    )
                )
            ),
        );
    }
}
