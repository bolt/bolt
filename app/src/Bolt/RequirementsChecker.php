<?php

namespace Bolt;

use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a simple way to check system requirements for Bolt
 *
 * @author Sebastian Klier <sebastian@sebastianklier.com>
 *
 **/
class RequirementsChecker
{
    private $app;
    private $requirements;

    public function __construct(\Bolt\Application $app)
    {
        $this->app = $app;
    }

    public function getRequirements()
    {
        return $this->requirements;
    }

    public function addRequirement(Requirement $requirement)
    {
        $this->requirements[] = $requirement;
    }

    /**
     * Run the requirement check and return the template
     *
     * @return The template, with results being available in the 'requirements' array
     */
    public function run()
    {
        foreach ($this->requirements as $requirement) {
            $requirement->check();
        }

        return $this->app['render']->render('requirements_check.twig', array('requirements' => $this->requirements));
    }
}

/**
 * This class represents a single requirement
 *
 * @author Sebastian Klier <sebastian@sebastianklier.com>
 *
 **/
class Requirement
{
    private $type;
    private $name;
    private $targetValue;
    private $currentValue;
    private $optional;
    private $explanation;
    private $fulfilled;

    /**
     * Constructor
     *
     * @param String $type Type of requirement, see switch statement, available are php, writable, ini, extension, class, function
     * @param String $name Name of the requirement,
     * for class, function, ini or extension, this needs to be the exact name,
     * for folders the path relative to the app root dir
     * @param String $targetValue Must be a PHP version or ini setting value respectively,
     * otherwise can be a string that will be displayed on the requirements page
     * @param boolean $optional Denotes optional requirements, currently no used
     * @param String $explanation Can hold an explanation of the requirement
     * that should be printed on the template page, currently not used
     **/
    public function __construct($type, $name, $targetValue = null, $optional = false, $explanation)
    {
        $this->type = strtolower($type);
        $this->name = $name;
        $this->targetValue = $targetValue;
        $this->optional = $optional;
        $this->explanation = $explanation;
        $this->fulfilled = false;
    }

    /**
     * Checks the state of this requirement and sets the $fulfilled variable accordingly
     */
    public function check()
    {
        switch ($this->type) {
            case 'php':
                $this->currentValue = PHP_VERSION;
                $this->fulfilled = (version_compare($this->currentValue, $this->targetValue, '>=')) ? true : false;
                break;
            case 'writable':
                $this->fulfilled = (is_dir($this->name) AND is_writable($this->name)) ? true : false;
                $this->currentValue = ($this->fulfilled) ? 'writable' : 'not writable' ;
                break;
            case 'ini':
                $this->currentValue = ini_get($this->name);
                if ($this->targetValue === 'set' && $this->currentValue !== '') {
                    $this->fulfilled = true;
                }
                elseif ($this->targetValue === 'off' && ( $this->currentValue === 'off' || $this->currentValue === false )) {
                    $this->currentValue = 'off';
                    $this->fulfilled = true;
                }
                break;
            case 'extension':
                $this->fulfilled = (extension_loaded($this->name)) ? true : false;
                $this->currentValue = ($this->fulfilled) ? 'installed' : 'not installed';
                break;
            case 'class':
                $this->fulfilled = (class_exists($this->name)) ? true : false;
                $this->currentValue = ($this->fulfilled) ? 'installed' : 'not installed';
                break;
            case 'function':
                $this->fulfilled = (function_exists($this->targetValue)) ? true : false;
                $this->currentValue = ($this->fulfilled) ? 'installed' : 'not installed';
                break;
            default:
                throw new \Exception('No valid requirement definition.');
        }
    }

    public function getName()
    {
        return $this->name;
    }


    public function getType()
    {
        return $this->type;
    }


    public function getTargetValue()
    {
        return $this->targetValue;
    }

    public function getCurrentValue()
    {
        return $this->currentValue;
    }

    public function getOptional()
    {
        return $this->optional;
    }

    public function getExplanation()
    {
        return $this->explanation;
    }

    public function getFulfilled()
    {
        return $this->fulfilled;
    }

}
