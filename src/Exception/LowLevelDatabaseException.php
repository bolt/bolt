<?php
namespace Bolt\Exception;

class LowLevelDatabaseException extends LowlevelException
{
    public static function failedConnect($platform, \Exception $previous)
    {
        $error = <<<TEXT
Bolt could not connect to the configured database.

Things to check:
&nbsp;&nbsp;* Ensure the $platform database is running
&nbsp;&nbsp;* Check the <code>database:</code> parameters are configured correctly in <code>app/config/config.yml</code>
&nbsp;&nbsp;&nbsp;&nbsp;* Database name is correct
&nbsp;&nbsp;&nbsp;&nbsp;* User name has access to the named database
&nbsp;&nbsp;&nbsp;&nbsp;* Password is correct
TEXT;

        return new static($error, $previous->getCode(), $previous);
    }

    public static function missingParameter($parameter)
    {
        return new static("There is no <code>$parameter</code> set for your database.");
    }

    public static function missingDriver($name, $driver)
    {
        return new static(
            sprintf(
                '%s was selected as the database type, but the driver does not exist or is not loaded. ' .
                'Please install the %s driver.',
                $name,
                $driver
            )
        );
    }

    public static function unsupportedDriver($driver)
    {
        return new static($driver . ' was selected as the database type, but it is not supported.');
    }

    public static function unsecure()
    {
        return new static(
            "There is no <code>password</code> set for the database connection, and you're using user 'root'.<br/> " .
            'That must surely be a mistake, right? ' .
            "Bolt will stubbornly refuse to run until you've set a password for 'root'."
        );
    }

    public static function unwritableFile($path)
    {
        return static::invalidPath('file', $path, 'is not writable');
    }

    public static function unwritableFolder($path)
    {
        return static::invalidPath('folder', $path, 'is not writable');
    }

    public static function nonexistantFile($path)
    {
        return static::invalidPath('file', $path, 'does not exist');
    }

    public static function nonexistantFolder($path)
    {
        return static::invalidPath('folder', $path, 'does not exist');
    }

    protected static function invalidPath($type, $path, $error)
    {
        return new static(
            sprintf(
                'The database %s <code>%s</code> %s. ' .
                "Make sure it's present and writable to the user that the webserver is using.",
                $type,
                $path,
                $error
            )
        );
    }
}
