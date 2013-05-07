define mysql::queryfile (
  $mysql_db,
  $mysql_file,
  $mysql_user           = 'root',
  $mysql_password       = '',
  $mysql_host           = 'localhost',
  $mysql_query_filepath = '/root/puppet-mysql'
  ) {

  exec { "mysqlqueryfile-${name}":
    command => "mysql ${mysql_db} < ${mysql_file} && touch ${mysql_query_filepath}/mysqlqueryfile-${name}.run",
    path    => [ '/usr/bin' , '/usr/sbin' , '/bin' , '/sbin' ],
    creates => "${mysql_query_filepath}/mysqlqueryfile-${name}.run",
    unless  => "ls ${mysql_query_filepath}/mysqlqueryfile-${name}.run",
  }

}
