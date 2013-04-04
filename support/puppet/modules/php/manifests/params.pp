# Class: php::params
#
# Defines php parameters
# In this class are defined as variables values that are used in other users classes
# This class should be included, where necessary, and eventually be enhanced with support for more OS
#
class php::params  {
## DEFAULTS FOR VARIABLES USERS CAN SET
# (Here are set the defaults, provide your custom variables externally)
#


# MODULES INTERNAL VARIABLES
# (Modify only to adapt to unsupported OSes)
    $packagename = $::operatingsystem ? {
            ubuntu  => "php5",
            debian  => "php5",
            suse    => "php5",
            default => "php",
            }

    $packagenamecommon = $::operatingsystem ? {
            ubuntu  => "php5-common",
            debian  => "php5-common",
            default => "php-common",
            }

    $configfile = $::operatingsystem ? {
        ubuntu  => "/etc/php5/apache2/php.ini",
        debian  => "/etc/php5/apache2/php.ini",
        default => "/etc/php.ini",
    }

    $configfile_mode = $::operatingsystem ? {
        default => "644",
    }

    $configfile_owner = $::operatingsystem ? {
        default => "root",
    }

    $configfile_group = $::operatingsystem ? {
        default => "root",
    }

    $configdir = $::operatingsystem ? {
        ubuntu  => "/etc/php5/conf.d",
        debian  => "/etc/php5/conf.d",
        default => "/etc/php/conf.d",
    }


## FILE SERVING SOURCE
# Sets the correct source for static files
# In order to provide files from different sources without modifying the module
# you can override the default source path setting the variable $base_source
# Ex: $base_source="puppet://ip.of.fileserver" or $base_source="puppet://$servername/myprojectmodule"
# What follows automatically manages the new source standard (with /modules/) from 0.25 

    case $base_source {
        '': {
            $general_base_source = $::puppetversion ? {
                /(^0.25)/ => "puppet:///modules",
                /(^0.)/   => "puppet://$servername",
                default   => "puppet:///modules",
            }
        }
        default: { $general_base_source=$base_source }
    }

}
