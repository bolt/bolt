class firewall::linux::debian {
  package { 'iptables-persistent':
    ensure => present,
  }

  # This isn't a real service/daemon. The start action loads rules, so just
  # needs to be called on system boot.
  service { 'iptables-persistent':
    ensure  => undef,
    enable  => true,
    require => Package['iptables-persistent'],
  }
}
