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

    # Setup a EPEL repo, the default one is disabled.
    /*
    file { "EpelRepo" :
        path   => "/etc/yum.repos.d/epel.repo",
        source => "${params::filepath}/bolt/files/epel.repo",
        owner  => "root",
        group  => "root",
        mode  => 0644,
    }
    */

}