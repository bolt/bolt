<?php

namespace Bolt;

class DeepDiff {
    public static function deep_diff($a, $b) {
        if (empty($a)) $a = array();
        if (empty($b)) $b = array();
        $keys = array_keys($a + $b);
        $result = [];
        foreach ($keys as $k) {
            if (empty($a[$k])) {
                $l = "";
            }
            else {
                $l = json_encode($a[$k]);
            }
            if (empty($b[$k])) {
                $r = "";
            }
            else {
                $r = json_encode($b[$k]);
            }
            if ($l != $r) {
                $result[] = [ $k, $l, $r ];
            }
        }
        return $result;
    }
}
