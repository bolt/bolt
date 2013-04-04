# Define puppi::ze
#
# The Puppi 2.0 define that transforms any class variable in data
# you can use with Puppi
#
# == Usage
# Basic Usage:
# puppi::ze { "openssh":
#   variables => get_class_args(),
# }
#
define puppi::ze (
  $variables,
  $helper = 'standard',
  $ensure = 'present' ) {

  require puppi
  require puppi::params

  file { "puppize_${name}":
    ensure  => $ensure,
    path    => "${puppi::params::datadir}/${helper}_${name}.yaml",
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    content => inline_template('<%= Hash[variables.sort].to_yaml %>'),
  }

  Puppi::Ze[$name] -> Class['puppi::is_installed']

}
