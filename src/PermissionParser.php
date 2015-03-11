<?php

namespace Bolt;

use Bolt\Exception\PermissionLexerException;
use Bolt\Exception\PermissionParserException;

/**
 * Lexer and parser for permission query syntax.
 *
 * Input is a stream of bytes, usually from a call to isAllowed(). Output is
 * a nested associative array representing permission query AST.
 *
 * Each node in the nested tree contains the following keys:
 * - 'type': one of the P_XXXX parse tree node type constants (see below)
 * - 'value': the node's "payload". For nodes that have children (P_AND, P_OR),
 *            this is an array of child nodes; for other nodes, it is the plain
 *            payload - a simple query of the form 'a:b:c:...' for P_SIMPLE, or
 *            NULL for P_TRUE and P_FALSE.
 *
 * Lexer tokens will not typically be used outside the parser. Each lexer token
 * is an associative array with the following keys:
 * - 'type': one of the T_XXXX constants, indicating the token type.
 * - 'capture': if the token captures variable input, this key holds the value.
 * - 'match': the raw input consumed by this token. For any valid input stream,
 *            concatenating the 'match' values for all the output tokens should
 *            yield back the original input.
 */
class PermissionParser
{
    // Token types:
    /**
     * Dummy type to signal lexer errors.
     */
    const T_UNDEFINED = 0;

    /**
     * Opening parens: '('.
     */
    const T_OPEN_PARENS = 1;

    /**
     * Closing parens: ')'.
     */
    const T_CLOSE_PARENS = 2;

    /**
     * 'OR' keyword or operator.
     */
    const T_OR = 3;

    /**
     * 'AND' keyword or operator.
     */
    const T_AND = 4;

    /**
     * A single query (a:b:c:...).
     */
    const T_QUERY = 5;

    /**
     * Whitespace. Skipped in the parser, but required to separate some tokens.
     */
    const T_SPACE = 6;

    /**
     * 'TRUE' keyword.
     */
    const T_TRUE = 7;

    /**
     * 'FALSE' keyword.
     */
    const T_FALSE = 8;

    /**
     * Get the symbolic name of a lexer token type.
     *
     * @param int $tokenType
     *
     * @return string
     */
    public static function tokenName($tokenType)
    {
        switch ($tokenType) {
            case self::T_UNDEFINED:
                return 'T_UNDEFINED';
            case self::T_OPEN_PARENS:
                return 'T_OPEN_PARENS';
            case self::T_CLOSE_PARENS:
                return 'T_CLOSE_PARENS';
            case self::T_OR:
                return 'T_OR';
            case self::T_AND:
                return 'T_AND';
            case self::T_QUERY:
                return 'T_QUERY';
            case self::T_SPACE:
                return 'T_SPACE';
            case self::T_TRUE:
                return 'T_TRUE';
            case self::T_FALSE:
                return 'T_FALSE';
            default:
                return '"' . (string) $tokenType . '"';
        }
    }

    // Parse tree node types

    /**
     * A single permission check of the form a:b:c:.
     */
    const P_SIMPLE = 0;

    /**
     * A list of child queries, combined with short-circuiting "OR" (i.e.,
     * first sub-check to pass short-circuits).
     */
    const P_OR = 1;

    /**
     * A list of child queries, combined with short-circuiting "AND" (i.e.,
     * first sub-check to fail short-circuits).
     */
    const P_AND = 2;

    /**
     * Always-pass dummy check.
     */
    const P_TRUE = 3;

    /**
     * Always-fail dummy check.
     */
    const P_FALSE = 4;

    /**
     * Lexes and parses the specified query string $what.
     *
     * @param $what
     *
     * @throws Exception Parser or lexer errors are thrown as
     *
     * @return array A parse tree.
     */
    public static function run($what)
    {
        return self::parse(self::lex($what));
    }

    /**
     * Lexes the given $query into lexer tokens.
     *
     * @param $query
     *
     * @throws \Bolt\Exception\PermissionLexerException
     *
     * @return array
     */
    public static function lex($query)
    {
        $originalQuery = $query;

        // A branch is defined as a regular expression to match, mapped onto
        // the resulting token type (in the 'type' key).
        // If the regular expression has capturing subexpressions, the first
        // of those is returned in the 'capture' key of the token. The complete
        // match is always returned in the 'match' key.
        // CAVEAT: the regular expression *must* include a start-of-input
        // assertion, otherwise the lexer will break, consuming from the start
        // of the input even when the expression matches in the middle.
        $branches = array(
            // one or more whitespace characters
            '/^\s+/' => self::T_SPACE,

            // parentheses are obvious
            '/^\(/' => self::T_OPEN_PARENS,
            '/^\)/' => self::T_CLOSE_PARENS,

            // OR operator: one or two pipe characters
            '/^(?:\|\|?)/' => self::T_OR,

            // AND operator: one or two ampersand characters
            '/^(?:&&?)/' => self::T_AND,

            // Keywords: case-insensitive OR, AND, TRUE, FALSE.
            // Word-boundary assertions are required to avoid being overly
            // greedy.
            '/^(?:\bor\b)/i'    => self::T_OR,
            '/^(?:\band\b)/i'   => self::T_AND,
            '/^(?:\btrue\b)/i'  => self::T_TRUE,
            '/^(?:\bfalse\b)/i' => self::T_FALSE,

            // A single permission query. We're using an explicit character
            // whitelist here to match slug characters only.
            '/^([a-zA-Z_0-9\-]+(:[a-zA-Z_0-9\-]+)*:?)/' => self::T_QUERY);

        $tokens = array();
        while (!empty($query)) {
            $token = null;
            // loop through the branches until a match is found
            foreach ($branches as $re => $type) {
                $matches = array();
                if (preg_match($re, $query, $matches)) {
                    // construct lexeme
                    $token = array('type' => $type);
                    if (isset($matches[1])) {
                        $token['capture'] = $matches[1];
                    } else {
                        $token['capture'] = null;
                    }
                    $token['match'] = $matches[0];

                    // consume input
                    $query = substr($query, strlen($matches[0]));
                    break;
                }
            }
            if ($token === null) {
                // None of the branches matches; this means we have encountered
                // invalid syntax.
                throw new PermissionLexerException("Unexpected character '" . $query[0] . "' while parsing query $originalQuery");
            }

            // Filter out whitespace early: no need to keep it around, since
            // we'd only ignore it in the parser, and it's easier to filter
            // here than add checks in the parser.
            if ($token['type'] !== self::T_SPACE) {
                $tokens[] = $token;
            }
        }

        return $tokens;
    }

    /**
     * Assert that the given $token's 'type' key is in the list of $expected
     * token types.
     *
     * @param array $expected List of token types (T_XXXX constants).
     * @param array $token    A lexer token, associative array.
     *
     * @throws PermissionParserException
     */
    private static function expect($expected, $token)
    {
        if (!in_array($token['type'], $expected)) {
            if (count($expected) === 1) {
                $expectedStr = self::tokenName($expected[0]);
            } else {
                $last = array_pop($expected);
                $expectedStr = 'one of ' . implode(', ', array_map(array('self', 'tokenName'), $expected)) . ' or ' . self::tokenName($last);
            }
            $actualStr = self::tokenName($token['type']);
            if ($token['match']) {
                $actualStr .= " ('" . addslashes($token['match']) . "')";
            }
            $actualStr .= ' <<< ' . json_encode($token) . ' >>> ';
            throw new PermissionParserException("Parser error: expected $expectedStr, but found $actualStr");
        }
    }

    /**
     * Parse a stream of lexer tokens ('lexemes') into a permission query AST.
     *
     * @param array $tokens An array or iterable of lexer tokens. The output of
     *                      `lex()` is suitable here.
     *
     * @return array A nested associative array representing the resulting
     *               parse tree.
     */
    public static function parse($tokens)
    {
        if (empty($tokens)) {
            return array('type' => self::P_TRUE, 'value' => '');
        } else {
            return self::parseAnd($tokens);
        }
    }

    private static function parseAnd(&$tokens)
    {
        $parts = array(self::parseOr($tokens));
        while (!empty($tokens)) {
            $nextToken = reset($tokens);
            if ($nextToken['type'] === self::T_AND) {
                // consume & recurse, then continue looping
                array_shift($tokens);
                $parts[] = self::parseOr($tokens);
            } else {
                // stop iteration
                break;
            }
        }
        if (count($parts) > 1) {
            return array('type' => self::P_AND, 'value' => $parts);
        } else {
            return $parts[0];
        }
    }

    private static function parseOr(&$tokens)
    {
        $parts = array(self::parseSimple($tokens));
        while (!empty($tokens)) {
            $nextToken = reset($tokens);
            if ($nextToken['type'] === self::T_OR) {
                // consume & recurse, then continue looping
                array_shift($tokens);
                $parts[] = self::parseSimple($tokens);
            } else {
                // stop iteration
                break;
            }
        }
        if (count($parts) > 1) {
            return array('type' => self::P_OR, 'value' => $parts);
        } else {
            return $parts[0];
        }
    }

    private static function parseSimple(&$tokens)
    {
        $token = array_shift($tokens);
        switch ($token['type']) {
            case self::T_OPEN_PARENS:
                $query = self::parseAnd($tokens);
                $token = array_shift($tokens);
                self::expect(array(self::T_CLOSE_PARENS), $token);

                return $query;
            case self::T_QUERY:
                return array('type' => self::P_SIMPLE, 'value' => $token['capture']);
            default:
                self::expect(array(self::T_OPEN_PARENS, self::T_QUERY), $token);
        }
    }
}
