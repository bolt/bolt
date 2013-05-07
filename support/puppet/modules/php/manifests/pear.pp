# Class: php::pear
#
# Installs Pear for PHP module
#
# Usage:
# include php::pear
#
class php::pear  {

    include php

    package { php-pear:
        name => $operatingsystem ? {
            default => "php-pear",
            },
        ensure => present,
    }

}

