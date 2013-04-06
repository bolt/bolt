define mysql::grant (
  $mysql_db,
  $mysql_user,
  $mysql_password,
  $mysql_privileges     = 'ALL',
  $mysql_host           = 'localhost',
  $mysql_grant_filepath = '/root/puppet-mysql'
  ) {

  require mysql

  if (!defined(File[$mysql_grant_filepath])) {
    file { $mysql_grant_filepath:
      path   => $mysql_grant_filepath,
      ensure => directory,
      owner  => root,
      group  => root,
      mode   => 0700,
    }
  }

  if ($mysql_db == '*') {
    $mysql_grant_file = "mysqlgrant-${mysql_user}-${mysql_host}-all.sql"
  } else {
    $mysql_grant_file = "mysqlgrant-${mysql_user}-${mysql_host}-${mysql_db}.sql"
  }

  file { $mysql_grant_file:
    ensure   => present,
    mode     => 0600,
    owner    => root,
    group    => root,
    path     => "${mysql_grant_filepath}/${mysql_grant_file}",
    content  => template('mysql/grant.erb'),
  }

  exec { "mysqlgrant-${mysql_user}-${mysql_host}-${mysql_db}":
    command     => $mysql::real_root_password ? {
      ''      => "mysql -uroot < ${mysql_grant_filepath}/${mysql_grant_file}",
      default => "mysql --defaults-file=/root/.my.cnf -uroot < ${mysql_grant_filepath}/${mysql_grant_file}",
    },
    require     => Service['mysql'],
    subscribe   => File[$mysql_grant_file],
    path        => [ '/usr/bin' , '/usr/sbin' ],
    refreshonly => true;
  }

}
