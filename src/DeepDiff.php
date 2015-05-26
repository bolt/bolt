<?php

namespace Bolt;

class DeepDiff
{
    public static function diff($a, $b)
    {
        if (empty($a)) {
            $a = [];
        }
        if (empty($b)) {
            $b = [];
        }
        $keys = array_keys($a + $b);
        $result = [];

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
                $result[] = [$k, $l, $r];
            }
        }

        return $result;
    }
}
