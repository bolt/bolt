# Puppet module: mysql

This is a Puppet mysql module from the second generation of Example42 Puppet Modules.

Made by Alessandro Franceschi / Lab42

Official site: http://www.example42.com

Official git repository: http://github.com/example42/puppet-mysql

Released under the terms of Apache 2 License.

This module requires functions provided by the Example42 Puppi module.

For detailed info about the logic and usage patterns of Example42 modules read README.usage on Example42 main modules set.

## USAGE - Module specific

* Set a specific (cleartext) mysql root password

        class { "mysql":
          root_password => 'mys4cr3',
        }

* Set a random password ( saved in /root/.my.cnf )

        class { "mysql":
          root_password => 'auto',          
        }

* Create a new grant and database

        mysql::grant { 'db1':
          mysql_privileges => 'ALL',
          mysql_password => 'pwd',
          mysql_db => 'db1',
          mysql_user => 'db1',
          mysql_host => 'host',
        }


## USAGE - Basic management

* Install mysql with default settings

        class { "mysql": }

* Disable mysql service.

        class { "mysql":
          disable => true
        }

* Disable mysql service at boot time, but don't stop if is running.

        class { "mysql":
          disableboot => true
        }

* Remove mysql package

        class { "mysql":
          absent => true
        }

* Enable auditing without without making changes on existing mysql configuration files

        class { "mysql":
          audit_only => true
        }


## USAGE - Overrides and Customizations
* Use custom sources for main config file 

        class { "mysql":
          source => [ "puppet:///modules/lab42/mysql/mysql.conf-${hostname}" , "puppet:///modules/lab42/mysql/mysql.conf" ], 
        }


* Use custom source directory for the whole configuration dir

        class { "mysql":
          source_dir       => "puppet:///modules/lab42/mysql/conf/",
          source_dir_purge => false, # Set to true to purge any existing file not present in $source_dir
        }

* Use custom template for main config file 

        class { "mysql":
          template => "example42/mysql/mysql.conf.erb",      
        }

* Define custom options that can be used in a custom template without the
  need to add parameters to the mysql class

        class { "mysql":
          template => "example42/mysql/mysql.conf.erb",    
          options  => {
            'LogLevel' => 'INFO',
            'UsePAM'   => 'yes',
          },
        }

* Automaticallly include a custom subclass

        class { "mysql:"
          my_class => 'mysql::example42',
        }


## USAGE - Example42 extensions management 
* Activate puppi (recommended, but disabled by default)
  Note that this option requires the usage of Example42 puppi module

        class { "mysql": 
          puppi    => true,
        }

* Activate puppi and use a custom puppi_helper template (to be provided separately with
  a puppi::helper define ) to customize the output of puppi commands 

        class { "mysql":
          puppi        => true,
          puppi_helper => "myhelper", 
        }

* Activate automatic monitoring (recommended, but disabled by default)
  This option requires the usage of Example42 monitor and relevant monitor tools modules

        class { "mysql":
          monitor      => true,
          monitor_tool => [ "nagios" , "monit" , "munin" ],
        }

* Activate automatic firewalling 
  This option requires the usage of Example42 firewall and relevant firewall tools modules

        class { "mysql":       
          firewall      => true,
          firewall_tool => "iptables",
          firewall_src  => "10.42.0.0/24",
          firewall_dst  => "$ipaddress_eth0",
        }



[![Build Status](https://travis-ci.org/example42/puppet-mysql.png?branch=master)](https://travis-ci.org/example42/puppet-mysql)
