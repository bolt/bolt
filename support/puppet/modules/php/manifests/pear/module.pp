# Define: php::pear::module
#
# Installs the defined php pear component
#
# Variables:
# $use_package (default="yes") - Tries to install pear module with the relevant package
#                                If set to "no" it installs the module via pear command
# $preferred_state (default="stable") - Define which preferred state to use when installing Pear modules via pear
#                                command line (when use_package=no)
# Usage:
# php::pear::module { packagename: }
# Example:
# php::pear::module { Crypt-CHAP: }
#
define php::pear::module ($use_package="yes", $preferred_state="stable") {

    include php::pear

case $use_package {
    yes: {
        package { "pear-${name}":
            name => $operatingsystem ? {
                ubuntu  => "php-${name}",
                debian  => "php-${name}",
                default => "pear-${name}",
                },
            ensure => present,
            notify => Service["apache"],
        }
    }
    default: {
        exec { "pear-${name}":
            command => "pear -d preferred_state=${preferred_state} install ${name}",
            unless  => "pear info ${name}",
            require => Package["php-pear"],
        }
    }
} # End Case

}

