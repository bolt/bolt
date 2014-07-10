<?php

namespace Bolt;

class PermissionParser {
    // Token types
    const T_SPACE = 0;
    const T_OPEN_PARENS = 1;
    const T_CLOSE_PARENS = 2;
    const T_OR = 3;
    const T_AND = 4;
    const T_QUERY = 5;

    // Parse tree node types
    const P_SIMPLE = 0;
    const P_OR = 1;
    const P_AND = 2;

    public static function lex($query) {
        $branches = array(
            '/^\s+/' => self::T_SPACE,
            '/^\(/' => self::T_OPEN_PARENS,
            '/^\)/' => self::T_CLOSE_PARENS,
            '/^(?:[oO][rR]\b|\|\|?)/' => self::T_OR,
            '/^(?:[aA][nN][dD]\b|\&\&?)/' => self::T_AND,
            '/^(\w+(:\w+)*)/' => self::T_QUERY);
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

                    // consume input
                    $query = substr($query, strlen($matches[0]));
                    break;
                }
            }
            if ($token === null) {
                throw new \Exception("Unexpected character in permission query lexer: " . $query[0]);
            }
            
            if ($token['type'] !== self::T_SPACE) {
                $tokens[] = $token;
            }
        }
        return $tokens;
    }

    public static function parse($tokens) {
        return self::parseAnd($tokens);
    }

    public static function run($what) {
        return self::parse(self::lex($what));
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
                if ($token['type'] !== self::T_CLOSE_PARENS) {
                    throw new \Exception('Parser error: expected T_CLOSE_PARENS, but found ' . $token['capture']);
                }
                return $query;
            case self::T_QUERY:
                return array('type' => self::P_SIMPLE, 'value' => $token['capture']);
            default:
                throw new \Exception('Parser error: expected T_OPEN_PARENS or T_QUERY, but found ' . $token['capture']);
        }
    }

}
