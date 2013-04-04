# Class: php::oci8
#
# Installs oci8 PHP module
#
# Usage:
# include php::oci8

class php::oci8  {

    include php
    include oracle::client

    exec { "Read_/root/manual-oci8":
        command => 'echo "To install php-oci8 execute: . /etc/profile.d/oracleclientenv.sh && pecl install oci8  - When propted type: instantclient,/opt/instantclient_11_2" > /root/manual-oci8',
        unless  => "pecl info oci8",
        require => File["/etc/profile.d/oracleclientenv.sh"],
    }

   file { "/etc/php5/conf.d/oci.ini":
       content => "extension=oci8.so",
       notify  => Service["apache"],
   }       

   package { "libaio1":
       ensure => "present",
   }

}

