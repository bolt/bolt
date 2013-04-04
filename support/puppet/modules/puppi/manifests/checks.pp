# Class puppi::checks
#
# Creates default system-wide checks to be used by the puppi check command
#
class puppi::checks {

  puppi::check { 'NTP_Sync':
    command  => "check_ntp -H ${puppi::params::ntp}" ,
    priority => '20' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'Disks_Usage':
    command  => 'check_disk -w 20% -c 10% -A' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'System_Load':
    command  => 'check_load -w 15,10,5 -c 30,25,20' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'Zombie_Processes':
    command  => 'check_procs -w 5 -c 10 -s Z' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'Local_Mail_Queue':
    command  => 'check_mailq -w 2 -c 5' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'Connected_Users':
    command  => 'check_users -w 5 -c 10' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'DNS_Resolution':
    command  => 'check_dns -H example.com' ,
    priority => '15' ,
    hostwide => 'yes' ,
  }

  Class['puppi::checks'] -> Class['puppi::is_installed']

}
