<?php
/**
 * @see https://github.com/bolt/composer-install
 */

namespace Bolt\Composer;

use Symfony\Component\Filesystem\Filesystem;

class BootstrapGenerator
{
    public $templateStart = <<<'EOD'
<?php
require_once "%s";
$configuration = new Bolt\Configuration\Composer(%s);

EOD;

    public $templateEnd = <<<'EOD'
$configuration->getVerifier()->disableApacheChecks();
$configuration->verify();
$app = new Bolt\Application(array('resources'=>$configuration));
$app->initialize();
$app->run();

EOD;

    /**
     * By default bolt installs app and assets in the same root folder.
     * This option allows a separate web root to be configured.
     *
     * @var bool
     **/
    public $webroot;

    /**
     * Configures name of folder above.
     *
     * @var string
     **/
    public $webname;

    public function __construct($webroot = false, $webname = 'public')
    {
        $this->webroot = $webroot;
        $this->webname = $webname;
    }

    /**
     * Main public function that creates and writes a Bootstrap file.
     *
     * @return string index.php location
     **/
    public function create()
    {
        $bootstrap = $this->generate();

        return $this->write($bootstrap);
    }

    /**
     * Generate method builds the bootstrap file as a string.
     *
     * @return string
     **/
    public function generate()
    {
        if ($this->webroot) {
            $autoload = "../vendor/autoload.php";
            $base = "dirname(__DIR__)";
        } else {
            $autoload = "vendor/autoload.php";
            $base = "__DIR__";
        }

        $template = '';
        $template .= sprintf($this->templateStart, $autoload, $base);

        if ($this->webroot) {
            $template .= $this->getPathCode('web', $this->webname);
            $template .= $this->getPathCode('files', $this->webname . '/files');
            $template .= $this->getPathCode('themebase', $this->webname . '/theme');
        }

        $template .= $this->templateEnd;

        return $template;
    }

    /**
     * Writes the generated template to the correct location.
     *
     * @param string $template
     *
     * @return string location
     */
    public function write($template)
    {
        $filesystem = new Filesystem();
        if ($this->webroot) {
            $filesystem->mkdir($this->webname);
            $location = $this->webname . '/index.php';
            $filesystem->dumpFile($location, $template);
        } else {
            $location = 'index.php';
            $filesystem->dumpFile($location, $template);
        }

        return $location;
    }

    /**
     * Generates a line of code to set paths.
     *
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    protected function getPathCode($name, $value)
    {
        $template = '$configuration->setPath("%s", "%s");' . PHP_EOL;

        return sprintf($template, $name, $value);
    }
}
