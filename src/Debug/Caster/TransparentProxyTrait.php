<?php

namespace Bolt\Debug\Caster;

use Symfony\Component\VarDumper\Caster\Caster;
use Symfony\Component\VarDumper\Cloner\AbstractCloner;
use Symfony\Component\VarDumper\Cloner\Stub;
use Symfony\Component\VarDumper\Cloner\VarCloner;

/**
 * Helper to make proxy classes transparent to VarDumper.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
trait TransparentProxyTrait
{
    /** @var bool */
    protected $transparent;

    /**
     * @return string
     */
    abstract protected function getProxiedClass();

    /**
     * @return object|array
     */
    abstract protected function getProxiedObject();

    /**
     * Enable or disable transparency to dumper.
     *
     * @param bool $bool
     */
    public function setTransparent($bool = true)
    {
        $this->transparent = (bool) $bool;
    }

    /**
     * Registers caster for this class.
     *
     * This allows VarDumper to make this proxy transparent when dumping.
     *
     * @param AbstractCloner $cloner
     */
    public static function registerCaster(AbstractCloner $cloner)
    {
        /**
         * Don't want this to be publicly accessible.
         *
         * @param TransparentProxyTrait $obj
         * @param array                 $a
         * @param Stub                  $stub
         * @param bool                  $isNested
         * @param int                   $filter
         *
         * @return array
         */
        $caster = static function ($obj, array $a, Stub $stub, $isNested, $filter) {
            if (!$obj->transparent) {
                return $a;
            }

            // Fake the class name
            $stub->class = $obj->getProxiedClass();

            $object = $obj->getProxiedObject();
            if (is_array($object)) {
                // Fake to look like array
                $a = $object;
                $stub->type = Stub::TYPE_ARRAY;
                $stub->class = Stub::ARRAY_ASSOC;
                $stub->value = count($a);
                $stub->handle = 0;
            } else {
                // Fake to look like inner object
                $refCls = new \ReflectionClass($object);
                $a = Caster::castObject($object, $refCls);

                // Fake handle to inner object handle
                (new VarCloner([
                    $refCls->getName() => function ($obj, $a, Stub $innerStub) use ($stub) {
                        $stub->handle = $innerStub->handle;
                    },
                ]))->cloneVar($object);
            }

            return $a;
        };

        $cloner->addCasters([
            static::class => $caster,
        ]);
    }
}
