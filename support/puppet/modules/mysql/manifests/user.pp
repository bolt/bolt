define mysql::user (
  $mysql_user,
  $mysql_password       = '',
  $mysql_password_hash  = '',
  $mysql_host           = 'localhost',
  $mysql_grant_filepath = '/root/puppet-mysql'
  ) {

  include mysql

  if (!defined(File[$mysql_grant_filepath])) {
    file {$mysql_grant_filepath:
      ensure => directory,
      path   => $mysql_grant_filepath,
      owner  => root,
      group  => root,
      mode   => 0700,
    }
  }

  $mysql_grant_file = "mysqluser-${mysql_user}-${mysql_host}.sql"

  file { $mysql_grant_file:
      ensure  => present,
      mode    => 0600,
      owner   => root,
      group   => root,
      path    => "${mysql_grant_filepath}/${mysql_grant_file}",
      content => template('mysql/user.erb'),
  }

  exec { "mysqluser-${mysql_user}-${mysql_host}":
      command     => "mysql --defaults-file=/root/.my.cnf -uroot < ${mysql_grant_filepath}/${mysql_grant_file}",
      require     => [ Service['mysql'], File['/root/.my.cnf'] ],
      subscribe   => File[$mysql_grant_file],
      path        => [ '/usr/bin' , '/usr/sbin' ],
      refreshonly => true,
  }

}
