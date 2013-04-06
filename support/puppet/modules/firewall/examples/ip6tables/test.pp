firewall { '000 allow foo':
  dport    => [7061, 7062],
  action   => accept,
  proto    => 'tcp',
  provider => 'ip6tables'
}

firewall { '001 allow boo':
  action      => accept,
  iniface     => 'eth0',
  sport       => 123,
  dport       => 123,
  proto       => 'tcp',
  destination => '::1/128',
  provider    => 'ip6tables'
}

firewall { '002 foo':
  dport    => 1233,
  proto    => 'tcp',
  action   => drop,
  provider => 'ip6tables'
}

firewall { '005 INPUT disregard DHCP':
  dport    => ['bootpc', 'bootps'],
  action   => drop,
  proto    => 'udp',
  provider => 'ip6tables'
}

firewall { '006 INPUT disregard netbios':
  port     => ['netbios-ns', 'netbios-dgm', 'netbios-ssn'],
  action   => drop,
  proto    => 'udp',
  provider => 'ip6tables'
}

firewall { '006 Disregard CIFS':
  dport    => 'microsoft-ds',
  action   => drop,
  proto    => 'tcp',
  provider => 'ip6tables'
}

firewall { '010 icmp':
  proto    => 'ipv6-icmp',
  icmp     => 'echo-reply',
  action   => accept,
  provider => 'ip6tables'
}

firewall { '010 INPUT allow loopback':
  iniface  => 'lo',
  chain    => 'INPUT',
  action   => accept,
  provider => 'ip6tables'
}

firewall { '050 INPUT drop invalid':
  state    => 'INVALID',
  action   => drop,
  provider => 'ip6tables'
}

firewall { '051 INPUT allow related and established':
  state    => ['RELATED', 'ESTABLISHED'],
  action   => accept,
  provider => 'ip6tables'
}

firewall { '053 INPUT allow ICMP':
  icmp     => '8',
  proto    => 'ipv6-icmp',
  action   => accept,
  provider => 'ip6tables'
}

firewall { '055 INPUT allow DNS':
  sport    => 'domain',
  proto    => 'udp',
  action   => accept,
  provider => 'ip6tables'
}

firewall { '999 FORWARD drop':
  chain    => 'FORWARD',
  action   => drop,
  provider => 'ip6tables'
}

firewall { '001 OUTPUT allow loopback':
  chain    => 'OUTPUT',
  outiface => 'lo',
  action   => accept,
  provider => 'ip6tables'
}

firewall { '100 OUTPUT drop invalid':
  chain    => 'OUTPUT',
  state    => 'INVALID',
  action   => drop,
  provider => 'ip6tables'
}
