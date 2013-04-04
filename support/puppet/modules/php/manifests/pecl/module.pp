# Define: php::pecl::module
#
# Installs the defined php pecl component
#
# Variables:
# $use_package (default="yes") - Tries to install pecl module with the relevant package
#                                If set to "no" it installs the module via pecl command
# $preferred_state (default="stable") - Define which preferred state to use when installing Pear modules via pecl
#                                command line (when use_package=no)
# $auto_answer (default="\n") - The answer(s) to give to pecl prompts for unattended installs
#
# Usage:
# php::pecl::module { packagename: }
# Example:
# php::pecl::module { Crypt-CHAP: }
#
define php::pecl::module ($use_package="yes", $preferred_state="stable", $auto_answer="\\n" ) {

    include php::pecl

case $use_package {
    yes: {
        package { "php-${name}":
            name => $operatingsystem ? {
                ubuntu  => "php5-${name}",
                debian  => "php5-${name}",
                default => "php-${name}",
                },
            ensure => present,
            notify => Service["apache"],
        }
    }
    default: {
        exec { "pecl-${name}":
            command => "printf \"${auto_answer}\" | pecl -d preferred_state=${preferred_state} install ${name}",
            unless  => "pecl info ${name}",
            require => Package["php-pear"],
        }
    }
} # End Case

}

