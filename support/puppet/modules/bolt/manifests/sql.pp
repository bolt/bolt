class bolt::sql {
    # should be switchable later on
    include mysql

    exec { 'create-db':
        unless => "/usr/bin/mysql -u${params::dbuser} -p${params::dbpass} ${params::dbname}",
        command => "/usr/bin/mysql -e \"create database ${params::dbname}; grant all on ${params::dbname}.* to ${params::dbuser}@localhost identified by '${params::dbpass}';\"",
        require => Service["mysql"],
    }
}
