# = Define: mysql::augeas
#
# Manage my.cnf through augeas
#
# Here's an example how to find the augeas path to a variable:
#
#     # augtool --noload
#     augtool> rm /augeas/load
#     rm : /augeas/load 781
#     augtool> set /augeas/load/myfile/lens @MySQL
#     augtool> set /augeas/load/myfile/incl /etc/my.cnf
#     augtool> load
#     augtool> print
#     ...
#     /files/etc/my.cnf/target = "mysqld"
#     /files/etc/my.cnf/target/local-infile = "0"
#     /files/etc/my.cnf/target/key_buffer = "16M"
#     /files/etc/my.cnf/target/max_allowed_packet = "16M"
#     ...
#     augtool> exit
#     #
#
# The value of 'target' is what you need to use as prefix
# and the part after target is the suffix.
#
# == Parameters
#
# [*name*]
#   Augeas path to entry to be modified.
#
# [*ensure*]
#   Standard puppet ensure variable
#
# [*target*]
#   Which my.cnf manipulate. Default is $mysql::config_file
#
# [*value*]
#   Value to set
#
# == Examples
#
# mysql::augeas {
#   'mysqld/key_buffer':
#     value  => '128M';
#   'mysqld/max_allowed_packet':
#     value  => '16M';
# }
#
define mysql::augeas (
  $ensure = present,
  $target = $mysql::config_file,
  $value  = ''
  ) {

  include mysql

  $namesplit = split($name, '/')

  $key = $namesplit[1]
  $section = $namesplit[0]

  $changes = $ensure ? {
    present => [ "set target[.='${section}']/${key} '${value}'" ],
    absent  => [ "rm target[.='${section}']/${key} " ],
  }

  augeas { "mysql_augeas-${name}":
    incl    => $target,
    lens    => 'MySQL.lns',
    changes => $changes,
    notify  => $mysql::manage_service_autorestart;
  }

}
