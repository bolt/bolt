firewall { '000 allow foo':
  dport  => [7061, 7062],
  action => accept,
  proto  => 'tcp',
}

firewall { '975 log test':
  state     => 'NEW',
  log_level => 'panic',
  jump      => 'LOG'
}

firewall { '001 allow boo':
  action      => accept,
  iniface     => 'eth0',
  sport       => '123',
  dport       => '123',
  proto       => 'tcp',
  destination => '1.1.1.0/24',
  source      => '2.2.2.0/24',
}

firewall { '100 snat for network foo2':
  chain    => 'POSTROUTING',
  jump     => 'MASQUERADE',
  proto    => 'all',
  outiface => 'eth0',
  source   => '10.1.2.0/24',
  table    => 'nat'
}

firewall { '999 bar':
  action => accept,
  dport  => '1233',
  proto  => 'tcp',
}

firewall { '002 foo':
  action => drop,
  dport  => '1233',
  proto  => 'tcp',
}

firewall { '010 icmp':
  action => accept,
  proto  => 'icmp',
  icmp   => 'echo-reply',
}

firewall { '010 INPUT allow loopback':
  action  => accept,
  iniface => 'lo',
  chain   => 'INPUT',
}

firewall { '005 INPUT disregard DHCP':
  action => drop,
  dport  => ['bootpc', 'bootps'],
  proto  => 'udp'
}

firewall { '006 INPUT disregard netbios':
  action => drop,
  proto  => 'udp',
  dport  => ['netbios-ns', 'netbios-dgm', 'netbios-ssn'],
}

firewall { '006 Disregard CIFS':
  action => drop,
  dport  => 'microsoft-ds',
  proto  => 'tcp'
}

firewall { '050 INPUT drop invalid':
  action => drop,
  state  => 'INVALID',
}

firewall { '051 INPUT allow related and established':
  action => accept,
  state  => ['RELATED', 'ESTABLISHED'],
}

firewall { '053 INPUT allow ICMP':
  action => accept,
  icmp   => '8',
  proto  => 'icmp',
}

firewall { '055 INPUT allow DNS':
  action => accept,
  proto  => 'udp',
  sport  => 'domain'
}

firewall { '056 INPUT allow web in and out':
  action => accept,
  proto  => 'tcp',
  port   => 80
}

firewall { '057 INPUT limit NTP':
  action => accept,
  proto  => 'tcp',
  dport  => ntp,
  limit  => '15/hour'
}

firewall { '999 FORWARD drop':
  action => drop,
  chain  => 'FORWARD',
}

firewall { '001 OUTPUT allow loopback':
  action   => accept,
  chain    => 'OUTPUT',
  outiface => 'lo',
}

firewall { '100 OUTPUT drop invalid':
  action => drop,
  chain  => 'OUTPUT',
  state  => 'INVALID',
}

resources { 'firewall':
  purge => true
}
