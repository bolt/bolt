# Define puppi::run
#
# This define triggers a puppi deploy run directly during Puppet
# execution. It can be used to automate FIRST TIME applications
# deployments directly during the first Puppet execution
#
# == Variables
#
# [*name*]
#   The title/name you use has to be the name of an existing puppi::project
#   procedure define
#
# == Usage
# Basic Usage:
# puppi::run { "myapp": }
#
define puppi::run (
  $project = '' ) {

  require puppi

  exec { "Run_Puppi_${name}":
    command => "puppi deploy ${name} && touch ${puppi::params::archivedir}/puppirun_${name}",
    path    => '/bin:/sbin:/usr/sbin:/usr/bin',
    creates => "${puppi::params::archivedir}/puppirun_${name}",
  }

  Puppi::Run[$name] -> Class['puppi::is_installed']

}
