class bolt::setup {

    file { '/etc/motd':
        ensure  => file,
        mode    => '0644',
        owner   => 'root',
        group   => 'root',
        source => "${params::filepath}/bolt/files/motd.txt",
    }

    # Install some default packages
    $default_packages = [ "git", "curl" ]
    package { $default_packages :
        ensure => present,
    }

    class { "epel": }

}