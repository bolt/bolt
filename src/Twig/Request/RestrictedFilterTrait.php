<?php

namespace Bolt\Twig\Request;

/**
 * Override filter function in order to restrict certain options in Twig context.
 * The list of restricted options are taken from "How to Harden Your PHP for
 * Better Security" [1].
 *
 * [1] https://howtogetonline.com/how-to-harden-your-php-for-better-security.php
 *
 * @author Xiao-Hu Tai <xiao@twokings.nl>
 */
trait RestrictedFilterTrait
{
    /** @var array */
    private $restrictedOptions = [
        '_getppid',
        'allow_url_fopen',
        'allow_url_include',
        'chgrp',
        'chmod',
        'chown',
        'curl_exec',
        'curl_multi_exec',
        'diskfreespace',
        'dl',
        'exec',
        'fpaththru',
        'getmypid',
        'getmyuid',
        'highlight_file',
        'ignore_user_abord',
        'ini_set',
        'lchgrp',
        'lchown',
        'leak',
        'link',
        'listen',
        'parse_ini_file',
        'passthru',
        'pcntl_exec',
        'php_uname',
        'phpinfo',
        'popen',
        'posix_ctermid',
        'posix_getcwd',
        'posix_getegid',
        'posix_geteuid',
        'posix_getgid',
        'posix_getgrgid',
        'posix_getgrnam',
        'posix_getgroups',
        'posix_getlogin',
        'posix_getpgid',
        'posix_getpgrp',
        'posix_getpid',
        'posix_getpwnam',
        'posix_getpwuid',
        'posix_getrlimit',
        'posix_getsid',
        'posix_getuid',
        'posix_isatty',
        'posix_kill',
        'posix_mkfifo',
        'posix_setegid',
        'posix_seteuid',
        'posix_setgid',
        'posix_setpgid',
        'posix_setsid',
        'posix_setuid',
        'posix_times',
        'posix_ttyname',
        'posix_uname',
        'posix',
        'proc_close',
        'proc_get_status',
        'proc_nice',
        'proc_open',
        'proc_terminate',
        'putenv',
        'set_time_limit',
        'shell_exec',
        'show_source',
        'source',
        'system',
        'tmpfile',
        'virtual',
    ];

    /**
     * {@inheritdoc}
     */
    public function filter($key, $default = null, $filter = FILTER_DEFAULT, $options = array(), $deep = false)
    {
        if (isset($options['options']) && in_array($options['options'], $this->restrictedOptions)) {
            unset($options['options']);
        }

        return parent::filter($key, $default, $filter, $options, $deep);
    }
}
