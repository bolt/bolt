class firewall::linux::redhat {
  service { 'iptables':
    ensure => running,
    enable => true,
  }
}
