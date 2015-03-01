<?php
namespace Bolt\Exception;

use Bolt\Configuration\ResourceManager;

class LowlevelException extends \Exception
{
    public static $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>%error_title%</title>
    <style>
        body{font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;color:#333;font-size:14px;line-height:20px;margin:0;}
        h1 {font-size: 38.5px;line-height: 40px;margin: 10px 0;}
        p{margin: 0 0 10px;}
        strong{font-weight:bold;}
        code, pre {padding: 0 3px 2px;font-family: Monaco,Menlo,Consolas,"Courier New",monospace;font-size: 12px;color: #333;border-radius: 3px;}
        code {padding: 2px 4px;color: #D14;background-color: #F7F7F9;border: 1px solid #E1E1E8;white-space: nowrap;}
        a {color: #08C;text-decoration: none;}
        ul, ol {padding: 0;margin: 0 0 10px 25px;}
        hr{margin:20px 0;border:0;border-top:1px solid #eeeeee;border-bottom:1px solid #ffffff;}
        .hide{display:none;}
        .status-ok {color: #468847;background-color: #DFF0D8;border-color: #D6E9C6;margin:5px;padding:5px;}
        .status-error {color: #B94A48;background-color: #F2DEDE;border-color: #EED3D7;margin:5px;padding:5px;}
    </style>
</head>
<body style="padding: 20px;">

    <div style="max-width: 640px; margin: auto;">

    <h1>%error_title%</h1>

    <p><strong>%error%</strong></p>

    %info%

    <ul>
        <li><a href="http://docs.bolt.cm/installation"><span class="hide"> * http://docs.bolt.cm/installation - </span>Bolt documentation - Setup</a></li>
        <li><a href="http://stackoverflow.com/questions/tagged/bolt-cms"><span class="hide"> * http://stackoverflow.com/questions/tagged/bolt-cms - </span>Bolt questions on Stack Overflow</a></li>
    </ul>

    </div>
    <hr>

</body>
</html>
HTML;

    public static $info = <<<HTML
    <p>This is a fatal error. Please fix the error, and refresh the page.
    Bolt can not run, until this error has been corrected. <br>
    Make sure you've read the instructions in the documentation for help. If you
    can't get it to work, post a message on our forum, and we'll try to help you
    out. Be sure to include the exact error message you're getting!</p>
HTML;

    /**
     * Print a 'low level' error page, and quit. The user has to fix something.
     *
     * Security caveat: the message is inserted into the page unescaped, so
     * make sure that it contains valid HTML with proper encoding applied.
     *
     * @param string $message
     * @param null   $code
     * @param null   $previous
     */
    public function __construct($message, $code = null, $previous = null)
    {
        $html = self::$html;
        $info = self::$info;

        $output = str_replace('%error_title%', 'Bolt - Fatal Error', $html);
        $message = nl2br($message);
        $output = str_replace('%error%', $message, $output);
        $output = str_replace('%info%', $info, $output);

        // TODO: Information disclosure vulnerability. A misconfigured system
        // will give an attacker detailed information about the state of the
        // system.
        // Suggested solution: in the config file, provide a whitelist of hosts
        // that may access the self-configuration functionality, and only
        // expose the information to hosts on the whitelist.

        // Determine if we're on the command line. If so, don't output HTML.
        if (php_sapi_name() == 'cli') {
            $output = self::cleanHTML($output);
        }

        echo $output;
    }

    public static function catchFatalErrors($app = null)
    {
        // Get last error, if any
        $error = error_get_last();

        // Let Whoops handle AJAX requested fatal errors
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') {
            return;
        }

        if (($error['type'] == E_ERROR || $error['type'] == E_PARSE)) {
            $html = self::$html;

            // Get the application object
            if ($app === null) {
                $app = ResourceManager::getApp();
            }

            // Detect if we're being called from a core, an extension or vendor
            $isBoltCoreError  = strpos($error['file'], $app['resources']->getPath('rootpath') . '/src');
            $isVendorError    = strpos($error['file'], $app['resources']->getPath('rootpath') . '/vendor');
            $isExtensionError = strpos($error['file'], $app['resources']->getPath('extensions'));

            // Assemble error trace
            $errorblock  = '<code>Error: ' . $error['message'] . '</code><br>';
            $errorblock .= '<code>File:  ' . $error['file'] . '</code><br>';
            $errorblock .= '<code>Line:  ' . $error['line'] . '</code><br><br>';

            if ($isBoltCoreError === 0) {
                $html = str_replace('%error_title%', 'PHP Fatal Error: Bolt Core', $html);
                $html = str_replace('%info%', '', $html);
                $message = $errorblock;
            } elseif ($isVendorError === 0) {
                $html = str_replace('%error_title%', 'PHP Fatal Error: Vendor Library', $html);
                $html = str_replace('%info%', '', $html);
                $message = $errorblock;
            } elseif ($isExtensionError === 0) {
                $base = str_replace($app['resources']->getPath('extensions'), '', $error['file']);
                $parts = explode(DIRECTORY_SEPARATOR, ltrim($base, '/'));
                $package = $parts[1] . '/' . $parts[2];

                $delete = 'extensions' . DIRECTORY_SEPARATOR . $parts[0] . DIRECTORY_SEPARATOR . $parts[1] . DIRECTORY_SEPARATOR . $parts[2];

                $html = str_replace('%error_title%', 'PHP Fatal Error: Bolt Extensions', $html);
                $html = str_replace(
                    '%info%',
                    '<p>You will only be able to continue by manually deleting the extension that is installed at:</p>' .
                    '<code>' . $delete . '</code><br><br>',
                    $html
                );
                $message  = '<h4>There is a fatal error in the \'' . $package . '\' extension ' .
                    'loaded on your Bolt Installation.<h4>';
                $message .= $errorblock;
            } else {
                // Unknown
                $html = str_replace('%error_title%', 'PHP Fatal Error: Bolt Generic', $html);
                $html = str_replace('%info%', '', $html);
                $message = $errorblock;
            }

            $message = nl2br($message);
            $html = str_replace('%error%', $message, $html);

            // Determine if we're on the command line. If so, don't output HTML.
            if (php_sapi_name() == 'cli') {
                $html = self::cleanHTML($html);
            }

            echo str_replace($app['resources']->getPath('rootpath'), '', $html);
        }
    }

    /**
     * Ignore exception handler pointed at by set_exception_handler().
     *
     * @param \Exception $e
     */
    public static function nullHandler(\Exception $e)
    {
    }

    private static function cleanHTML($output)
    {
        $output = preg_replace('/<title>.*<\/title>/smi', "", $output);
        $output = preg_replace('/<style>.*<\/style>/smi', "", $output);
        $output = strip_tags($output);
        $output = preg_replace('/(\n+)(\s+)/smi', "\n", $output);

        return $output;
    }
}
