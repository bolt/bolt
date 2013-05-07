# Class: apache::params
#
# This class defines default parameters used by the main module class apache
# Operating Systems differences in names and paths are addressed here
#
# == Variables
#
# Refer to apache class for the variables defined here.
#
# == Usage
#
# This class is not intended to be used directly.
# It may be imported or inherited by other classes
#
class apache::params {

  ### Application specific parameters
  $package_modssl = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint)/ => 'libapache-mod-ssl',
    default                   => 'mod_ssl',
  }

  ### Application related parameters

  $package = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint)/ => 'apache2',
    default                   => 'httpd',
  }

  $service = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint)/ => 'apache2',
    default                   => 'httpd',
  }

  $service_status = $::operatingsystem ? {
    default => true,
  }

  $process = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint)/ => 'apache2',
    default                   => 'httpd',
  }

  $process_args = $::operatingsystem ? {
    default => '',
  }

  $process_user = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint)/ => 'www-data',
    default                   => 'apache',
  }

  $config_dir = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint)/ => '/etc/apache2',
    freebsd                   => '/usr/local/etc/apache20',
    default                   => '/etc/httpd',
  }

  $config_file = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint)/ => '/etc/apache2/apache2.conf',
    freebsd                   => '/usr/local/etc/apache20/httpd.conf',
    default                   => '/etc/httpd/conf/httpd.conf',
  }

  $config_file_mode = $::operatingsystem ? {
    default => '0644',
  }

  $config_file_owner = $::operatingsystem ? {
    default => 'root',
  }

  $config_file_group = $::operatingsystem ? {
    freebsd => 'wheel',
    default => 'root',
  }

  $config_file_init = $::operatingsystem ? {
    /(?i:Debian|Ubuntu|Mint)/ => '/etc/default/apache2',
    default                   => '/etc/sysconfig/httpd',
  }

  $pid_file = $::operatingsystem ? {
    /(?i:Debian|Ubuntu|Mint)/ => '/var/run/apache2.pid',
    default                   => '/var/run/httpd.pid',
  }

  $log_dir = $::operatingsystem ? {
    /(?i:Debian|Ubuntu|Mint)/ => '/var/log/apache2',
    default                   => '/var/log/httpd',
  }

  $log_file = $::operatingsystem ? {
    /(?i:Debian|Ubuntu|Mint)/ => ['/var/log/apache2/access.log','/var/log/apache2/error.log'],
    default                   => ['/var/log/httpd/access.log','/var/log/httpd/error.log'],
  }

  $data_dir = $::operatingsystem ? {
    /(?i:Debian|Ubuntu|Mint)/ => '/var/www',
    /(?i:Suse|OpenSuse)/      => '/srv/www',
    default                   => '/var/www/html',
  }

  $port = '80'
  $protocol = 'tcp'

  # General Settings
  $my_class = ''
  $source = ''
  $source_dir = ''
  $source_dir_purge = ''
  $template = ''
  $options = ''
  $service_autorestart = true
  $absent = false
  $disable = false
  $disableboot = false

  ### General module variables that can have a site or per module default
  $monitor = false
  $monitor_tool = ''
  $monitor_target = $::ipaddress
  $firewall = false
  $firewall_tool = ''
  $firewall_src = '0.0.0.0/0'
  $firewall_dst = $::ipaddress
  $puppi = false
  $puppi_helper = 'standard'
  $debug = false
  $audit_only = false

}
