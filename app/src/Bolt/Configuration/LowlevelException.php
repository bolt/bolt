<?php
namespace Bolt\Configuration;

class LowlevelException extends \Exception
{

    public static $html = <<< EOM
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Bolt - Error</title>
    <style>
        body{font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;color:#333;font-size:14px;line-height:20px;margin:0px;}
        h1 {font-size: 38.5px;line-height: 40px;margin: 10px 0px;}
        p{margin: 0px 0px 10px;}
        strong{font-weight:bold;}
        code, pre {padding: 0px 3px 2px;font-family: Monaco,Menlo,Consolas,"Courier New",monospace;font-size: 12px;color: #333;border-radius: 3px;}
        code {padding: 2px 4px;color: #D14;background-color: #F7F7F9;border: 1px solid #E1E1E8;white-space: nowrap;}
        a {color: #08C;text-decoration: none;}
        ul, ol {padding: 0px;margin: 0px 0px 10px 25px;}
        hr{margin:20px 0;border:0;border-top:1px solid #eeeeee;border-bottom:1px solid #ffffff;}
        .hide{display:none;}
        .status-ok {background-color:#3C763D;color:white}
        .status-error {background-color:#A94442;color:white;}
    </style>
</head>
<body style="padding: 20px;">

    <div style="max-width: 530px; margin: auto;">

    <h1>Bolt - Fatal error.</h1>

    <p><strong>%error%</strong></p>

    <p>This is a fatal error. Please fix the error, and refresh the page.
    Bolt can not run, until this error has been corrected. <br>
    Make sure you've read the instructions in the documentation for help. If you
    can't get it to work, post a message on our forum, and we'll try to help you
    out. Be sure to include the exact error message you're getting!</p>

    <ul>
        <li><a href="http://docs.bolt.cm/installation"><span class="hide"> * http://docs.bolt.cm/installation - </span>Bolt documentation - Setup</a></li>
        <li><a href="http://stackoverflow.com/questions/tagged/bolt-cms"><span class="hide"> * http://stackoverflow.com/questions/tagged/bolt-cms - </span>Bolt questions on Stack Overflow</a></li>
    </ul>

    </div>
    <hr>

</body>
</html>
EOM;

    /**
     * Print a 'low level' error page, and quit. The user has to fix something.
     *
     * Security caveat: the message is inserted into the page unescaped, so
     * make sure that it contains valid HTML with proper encoding applied.
     *
     * @param string $message
     */
    public function __construct($message, $code=null, $previous=null)
    {
        $html = self::$html;
        $output = str_replace('%error%', $message, $html);

        // TODO: Information disclosure vulnerability. A misconfigured system
        // will give an attacker detailed information about the state of the
        // system.
        // Suggested solution: in the config file, provide a whitelist of hosts
        // that may access the self-configuration functionality, and only
        // expose the information to hosts on the whitelist.

        // Determine if we're on the Command line. If so, don't output HTML.
        if (php_sapi_name() == 'cli') {
            $output = preg_replace('/<title>.*<\/title>/smi', "", $output);
            $output = preg_replace('/<style>.*<\/style>/smi', "", $output);
            $output = strip_tags($output);
            $output = preg_replace('/(\n+)(\s+)/smi', "\n", $output);
        }

        echo $output;

        die();
    }
}
