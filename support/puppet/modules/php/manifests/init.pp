# Class: php
#
# Installs Php 
#
# Usage:
# include php
#
class php  {

    include php::params

    package { "php":
        name   => "${php::params::packagename}",
        ensure => present,
    }

    package { "php-common":
        name   => "${php::params::packagenamecommon}",
        ensure => present,
    }

    file { "php.ini":
        path    => "${php::params::configfile}",
        mode    => "${php::params::configfile_mode}",
        owner   => "${php::params::configfile_owner}",
        group   => "${php::params::configfile_group}",
        require => Package["php"],
	notify  => Service["apache"],
        ensure  => present,
#	source  => [
#            "puppet:///php/php.ini--$hostname",
#            "puppet:///php/php.ini-$role-$type",
#            "puppet:///php/php.ini-$role",
#            "puppet:///php/php.ini"
#        ],
    }

}

