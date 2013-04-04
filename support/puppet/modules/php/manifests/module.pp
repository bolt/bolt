# Define: php::module
#
# Installs the defined php module
#
# Usage:
# php::module { modulename: }
# Example:
# php::module { mysql: }
#
define php::module {

    include php

    package { "php-${name}":
        name => $::operatingsystem ? {
            ubuntu  => "php5-${name}",
            debian  => "php5-${name}",
            default => "php-${name}",
            },
        ensure => present,
        notify => Service["apache"],
    }
}
