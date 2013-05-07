define mysql::query (
  $mysql_db,
  $mysql_query,
  $mysql_user           = 'root',
  $mysql_password       = '',
  $mysql_host           = 'localhost',
  $mysql_query_filepath = '/root/puppet-mysql'
  ) {

  file { "mysqlquery-${name}.sql":
    ensure  => present,
    mode    => 0600,
    owner   => root,
    group   => root,
    path    => "${mysql_query_filepath}/mysqlquery-${name}.sql",
    content => template('mysql/query.erb'),
    notify  => Exec["mysqlquery-${name}"],
    require => Service['mysql'],
  }

  exec { "mysqlquery-${name}":
      command   => $mysql::real_root_password ? {
        ''      => "mysql -uroot < ${mysql_query_filepath}/mysqlquery-${name}.sql",
        default => "mysql --defaults-file=/root/.my.cnf -uroot < ${mysql_query_filepath}/mysqlquery-${name}.sql",
      },
      require     => File["mysqlquery-${name}.sql"],
      refreshonly => true,
      subscribe   => File["mysqlquery-${name}.sql"],
      path        => [ '/usr/bin' , '/usr/sbin' ],
  }

}
