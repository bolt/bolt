# Class: php::dev
#
# Installs Pear for PHP module
#
# Usage:
# include php::dev

class php::dev  {

    include php

    package { php-dev:
        name => $operatingsystem ? {
            ubuntu  => "php5-dev",
            debian  => "php5-dev",
            default => "php-dev",
            },
        ensure => present,
    }

}

