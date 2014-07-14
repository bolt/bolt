<?php

namespace Bolt;

class PermissionParser {
    // Token types
    const T_UNDEFINED = 0;
    const T_OPEN_PARENS = 1;
    const T_CLOSE_PARENS = 2;
    const T_OR = 3;
    const T_AND = 4;
    const T_QUERY = 5;
    const T_SPACE = 6;
    const T_TRUE = 7;
    const T_FALSE = 8;

    public static function tokenName($token) {
        switch ($token) {
            case self::T_UNDEFINED: return 'T_UNDEFINED';
            case self::T_OPEN_PARENS: return 'T_OPEN_PARENS';
            case self::T_CLOSE_PARENS: return 'T_CLOSE_PARENS';
            case self::T_OR: return 'T_OR';
            case self::T_AND: return 'T_AND';
            case self::T_QUERY: return 'T_QUERY';
            case self::T_SPACE: return 'T_SPACE';
            case self::T_TRUE: return 'T_TRUE';
            case self::T_FALSE: return 'T_FALSE';
            default: return '"' . (string)$token . '"';
        }
    }

    // Parse tree node types
    const P_SIMPLE = 0;
    const P_OR = 1;
    const P_AND = 2;
    const P_TRUE = 3;
    const P_FALSE = 4;

    public static function run($what) {
        return self::parse(self::lex($what));
    }

    public static function lex($query) {
        $originalQuery = $query;
        $branches = array(
            '/^\s+/' => self::T_SPACE,
            '/^\(/' => self::T_OPEN_PARENS,
            '/^\)/' => self::T_CLOSE_PARENS,
            '/^(?:\|\|?)/i' => self::T_OR,
            '/^(?:&&?)/i' => self::T_AND,
            '/^(?:\bor\b)/i' => self::T_OR,
            '/^(?:\band\b)/i' => self::T_AND,
            '/^(?:\btrue\b)/i' => self::T_TRUE,
            '/^(?:\bfalse\b)/i' => self::T_FALSE,
            '/^([a-zA-Z_0-9\-]+(:[a-zA-Z_0-9\-]+)*:?)/' => self::T_QUERY);
        $tokens = array();
        while (!empty($query)) {
            $token = null;
            foreach ($branches as $re => $type) {
                $matches = array();
                if (preg_match($re, $query, $matches)) {
                    // construct lexeme
                    $token = array('type' => $type);
                    if (isset($matches[1])) {
                        $token['capture'] = $matches[1];
                    }
                    else {
                        $token['capture'] = null;
                    }
                    $token['match'] = $matches[0];

                    // consume input
                    $query = substr($query, strlen($matches[0]));
                    break;
                }
            }
            if ($token === null) {
                throw new \Exception("Unexpected character '" . $query[0] . "' while parsing query $originalQuery");
            }

            if ($token['type'] !== self::T_SPACE) {
                $tokens[] = $token;
            }
        }
        return $tokens;
    }

    private static function expect($expected, $token) {
        if (!in_array($token['type'], $expected)) {
            if (count($expected) === 1) {
                $expectedStr = self::tokenName($expected[0]);
            }
            else {
                $last = array_pop($expected);
                $expectedStr = 'one of ' . implode(', ', array_map(array(self, 'tokenName'), $expected)) . ' or ' . self::tokenName($last);
            }
            $actualStr = self::tokenName($token['type']);
            if ($token['match']) {
                $actualStr .= " ('" . addslashes($token['match']) . "')";
            }
            $actualStr .= ' <<< ' . json_encode($token) . ' >>> ';
            throw new \Exception("Parser error: expected $expectedStr, but found $actualStr");
        }
    }

    public static function parse($tokens) {
        if (empty($tokens)) {
            return array('type' => self::P_TRUE, 'value' => '');
        }
        else {
            return self::parseAnd($tokens);
        }
    }

    private static function parseAnd(&$tokens) {
        $parts = array(self::parseOr($tokens));
        while (!empty($tokens)) {
            $nextToken = reset($tokens);
            if ($nextToken['type'] === self::T_AND) {
                // consume & recurse, then continue looping
                array_shift($tokens);
                $parts[] = self::parseOr($tokens);
            }
            else {
                // stop iteration
                break;
            }
        }
        if (count($parts) > 1) {
            return array('type' => self::P_AND, 'value' => $parts);
        }
        else {
            return $parts[0];
        }
    }

    private static function parseOr(&$tokens) {
        $parts = array(self::parseSimple($tokens));
        while (!empty($tokens)) {
            $nextToken = reset($tokens);
            if ($nextToken['type'] === self::T_OR) {
                // consume & recurse, then continue looping
                array_shift($tokens);
                $parts[] = self::parseSimple($tokens);
            }
            else {
                // stop iteration
                break;
            }
        }
        if (count($parts) > 1) {
            return array('type' => self::P_OR, 'value' => $parts);
        }
        else {
            return $parts[0];
        }
    }

    private static function parseSimple(&$tokens) {
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
