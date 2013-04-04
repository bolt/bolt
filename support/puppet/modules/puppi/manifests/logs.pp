#
# Class puppi::logs
#
# Creates default system-wide logs locations for diffent OSes
# Used by the puppi log command
#
class puppi::logs {

  case $::operatingsystem {

    Debian,Ubuntu: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => ['/var/log/messages','/var/log/syslog'],
      }
      puppi::log { 'auth':
        description => 'Users and authentication',
        log         => ['/var/log/user.log','/var/log/auth.log'],
      }
      puppi::log { 'mail':
        description => 'Mail messages',
        log         => '/var/log/mail.log',
      }
    }

    RedHat,CentOS,Scientific,Amazon,Linux: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => '/var/log/messages',
      }
      puppi::log { 'auth':
        description => 'Users and authentication',
        log         => '/var/log/secure',
      }
      puppi::log { 'mail':
        description => 'Mail messages',
        log         => '/var/log/maillog',
      }
    }

    Solaris: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => '/var/adm/messages',
      }
      puppi::log { 'auth':
        description => 'Users and authentication',
        log         => '/var/log/authlog',
      }
    }

    Archlinux: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => ['/var/log/messages.log','/var/log/syslog.log'],
      }
      puppi::log { 'auth':
        description => 'Users and authentication',
        log         => ['/var/log/user.log','/var/log/auth.log'],
      }
      puppi::log { 'mail':
        description => 'Mail messages',
        log         => '/var/log/mail.log',
      }
    }

    default: { }

  }

  Class['puppi::logs'] -> Class['puppi::is_installed']

}

