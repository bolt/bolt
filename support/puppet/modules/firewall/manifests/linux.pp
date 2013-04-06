class firewall::linux {
  package { 'iptables':
    ensure => present,
  }

  case $::operatingsystem {
    'RedHat', 'CentOS', 'Fedora': {
      class { "${title}::redhat":
        require => Package['iptables'],
      }
    }
    'Debian', 'Ubuntu': {
      class { "${title}::debian":
        require => Package['iptables'],
      }
    }
    default: {}
  }
}
