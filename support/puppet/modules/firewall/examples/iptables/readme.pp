  firewall { '000 allow packets with valid state':
    state   => ['RELATED', 'ESTABLISHED'],
    action  => 'accept',
  }
  firewall { '001 allow icmp':
    proto   => 'icmp',
    action  => 'accept',
  }
  firewall { '002 allow all to lo interface':
    iniface => 'lo',
    action  => 'accept',
  }
  firewall { '100 allow http':
    proto  => 'tcp',
    dport  => '80',
    action => 'accept',
  }
  firewall { '100 allow ssh':
    proto  => 'tcp',
    dport  => '22',
    action => 'accept',
  }
  firewall { '100 allow mysql from internal':
    proto  => 'tcp',
    dport  => '3036',
    source => '10.5.5.0/24',
    action => 'accept',
  }
  firewall { '999 drop everything else':
    action => 'drop',
  }

  resources { 'firewall':
    purge => true,
  }
