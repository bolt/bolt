# Class: puppi
#
# This is Puppi. Include it and things happen.
#
# Puppi is a Puppet module that has 2 functions:
# - Make Application deployments easy
# - Transfer "Puppet Knowledge to the shell"
#
class puppi {

  require puppi::params

  # Main configuration file
  file { 'puppi.conf':
    ensure  => present,
    path    => "${puppi::params::basedir}/puppi.conf",
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    content => template('puppi/puppi.conf.erb'),
    before  => Class['puppi::is_installed'],
    require => File['puppi_basedir'],
  }

  # The Puppi command
  file { 'puppi':
    ensure  => present,
    path    => '/usr/sbin/puppi',
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    content => template('puppi/puppi.erb'),
    before  => Class['puppi::is_installed'],
    require => File['puppi_basedir'],
  }

  # Puppi common scripts
  file { 'puppi.scripts':
    ensure  => present,
    path    => "${puppi::params::scriptsdir}/",
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    source  => "${puppi::params::general_base_source}/puppi/scripts/",
    recurse => true,
#   purge   => true,
    ignore  => '.svn',
    before  => Class['puppi::is_installed'],
    require => File['puppi_basedir'],
  }

  # Create Puppi workdirs
  include puppi::skel

  # To show some of the Puppi features some System-Wide general defines:
  # General system logs for puppi log
  include puppi::logs
  # General system infos for puppi info
  include puppi::infos
  # General system checks for puppi check
  include puppi::checks

  # Include prerequisits
  include puppi::prerequisites

  # This will create the Class['puppi::is_installed']
  # which marks the point where all Puppi files are installed
  # and before Puppi::Run
  include puppi::is_installed

}
