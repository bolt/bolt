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
    </style>
</head>
<body style="padding: 20px;">

    <div style="max-width: 530px; margin: auto;">
        <h1>Bolt - Fatal error.</h1>
        <p>
            <strong>
                Bolt requires PHP <u>5.3.3</u> or higher. You have PHP 
                <u><?=htmlspecialchars(PHP_VERSION, ENT_QUOTES)?></u>, so Bolt will not run on your current setup.
            </strong>
        </p>
    </div>
    <hr>
</body>
</html>
