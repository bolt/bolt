# Class: php::soap
#
# Installs SOAP for PHP module
#
# Usage:
# include php::soap

class php::soap  {

    include php

    package { php-soap:
        name => $operatingsystem ? {
            default => "php-soap",
            },
        ensure => present,
    }

}

