<?php

namespace Bolt;

class DeepDiff
{
    public static function diff($a, $b)
    {
        if (empty($a)) {
            $a = array();
        }
        if (empty($b)) {
            $b = array();
        }
        $keys = array_keys($a + $b);
        $result = array();

        foreach ($keys as $k) {
            if (empty($a[$k])) {
                $l = null;
            } else {
                $l = $a[$k];
            }
            if (empty($b[$k])) {
                $r = null;
            } else {
                $r = $b[$k];
            }
            if ($l != $r) {
                $result[] = array($k, $l, $r);
            }
        }

        return $result;
    }
}
