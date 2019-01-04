<?php

namespace Bolt\Helpers;

use Bolt\Collection\Bag;
use Bolt\Collection\MutableBag;
use Bolt\Common\Json;
use Symfony\Component\HttpFoundation\Request;

/**
 * Exception request sanitiser.
 *
 * @internal only to be used to sanitise Request objects for Twig exception renders
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class RequestSanitiser
{
    /**
     * Filter a request object to be safe to pass to Twig.
     *
     * @param Request $request
     *
     * @return Bag
     */
    public static function filter(Request $request)
    {
        $bags = MutableBag::from(array_fill_keys(['attributes', 'query', 'files', 'cookies', 'headers', 'server'], null));
        foreach ($bags->keys() as $key) {
            $bags[$key] = static::getValues($request->$key->all());
        }
        if ($request->hasSession()) {
            $bags['session'] = static::getValues($request->getSession()->all());
        }

        return $bags->immutable();
    }

    /**
     * @param array $values
     *
     * @return Bag
     */
    private static function getValues(array $values)
    {
        $bag = MutableBag::from($values);
        foreach ($bag as $k => $v) {
            /** @var Bag $v */
            if (is_array($v) && !is_callable($v)) {
                $bag[$k] = Bag::from($v)
                    ->call(function (array $a) {
                        return [Json::dump($a)];
                    })
                    ->join(' ')
                ;
            } elseif (is_string($v) || (is_object($v) && method_exists($v, '__toString'))) {
                $bag[$k] = (string) $v;
            } else {
                $bag->remove($k);
            }
        }

        return $bag->immutable();
    }
}
