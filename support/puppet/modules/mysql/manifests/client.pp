# Class: mysql::client
#
# Manages mysql client installation
#
# Usage:
# include mysql::client
#
class mysql::client {

  include mysql::params

  package { 'mysql-client':
    ensure => present,
    name   => $mysql::params::package_client,
  }

}

