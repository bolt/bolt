class bolt::web {

    include apache

    if $params::phpmyadmin == true {
        include bolt::web::phpmyadmin
    }

    resources { "firewall":
      purge => true
    }

    class { "firewall":
    }

    firewall { "00080 http on port 80":
      proto => "tcp",
      dport => "80",
      action => "accept",
      notify => Exec["iptables-save"],
    }

    exec { "iptables-save":
      command => $operatingsystem ? {
        "debian" => "/sbin/iptables-save > /etc/iptables/rules.v4",
        /(RedHat|CentOS)/ => "/sbin/iptables-save > /etc/sysconfig/iptables",
      },
      refreshonly => true,
      notify => Service["iptables"],
    }

    # Change user / group
    exec { "UsergroupChange" :
        command => "sed -i 's/User apache/User vagrant/ ; s/Group apache/Group vagrant/' /etc/httpd/conf/httpd.conf",
        onlyif  => "grep -c 'User apache' /etc/httpd/conf/httpd.conf",
        require => Package["apache"],
        notify  => Service['apache'],
    }

    file { "/var/lib/php/session" :
        owner  => "root",
        group  => "vagrant",
        mode   => 0770,
        require => Package["php"],
    }


    # Configure apache virtual host
    apache::vhost { $params::host :
        docroot   => "/vagrant",
        template  => "bolt/vhost.conf.erb",
        port      => $port,
    }

    # Install PHP modules
    php::module { "mysql" : }
    php::module { "xml" : }
    php::module { "pecl-apc" : }

    php::module { "pecl-xdebug" :
        # xdebug is in the epel repo
        #require => File["EpelRepo"],
    }

    # Set development values to our php.ini and xdebug.ini
    augeas { 'set-php-ini-values':
        context => '/files/etc/php.ini',
        changes => [
            'set PHP/error_reporting "E_ALL | E_STRICT"',
            'set PHP/display_errors On',
            'set PHP/display_startup_errors On',
            'set PHP/html_errors On',
            'set Date/date.timezone Europe/Amsterdam',
        ],
        require => Package['php'],
        notify  => Service['apache'],
    }

    augeas { 'set-xdebug-ini-values':
        context => '/files/etc/php.d/xdebug.ini',
        changes => [
            'set Xdebug/xdebug.remote_enable On',
            'set Xdebug/xdebug.remote_connect_back On',
            'set Xdebug/xdebug.remote_port 9000',
            'set Xdebug/xdebug.remote_handler dbgp',
            'set Xdebug/xdebug.remote_autostart On',
            'set Xdebug/xdebug.remote_log /vagrant/xdebug.log',
        ],
        require => Package['php'],
        notify  => Service['apache'],
    }


    # Install PEAR
    package { "php-pear" :
        ensure => present,
        require => Package['php'],
    }

}