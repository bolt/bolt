# Define puppi::info::readme
#
# This is a puppi info plugin that provides a Readme text which can be
# used to show local info on the managed server and eventually run custom commands.
#
#  puppi::info::readme { "myapp":
#    description => "Guidelines for myapp setup",
#    readme => "myapp/readme.txt" ,
#    run     => "myapp -V",
#  }
#
define puppi::info::readme (
  $description  = '',
  $readme       = '',
  $autoreadme   = 'no',
  $run          = '',
  $templatefile = 'puppi/info/readme.erb' ) {

  require puppi
  require puppi::params

  $bool_autoreadme = any2bool($autoreadme)

  file { "${puppi::params::infodir}/${name}":
    ensure  => present,
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => Class['puppi'],
    content => template($templatefile),
    tag     => 'puppi_info',
  }

  file { "${puppi::params::readmedir}/${name}":
    ensure  => present,
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_readmedir'],
    source  => $readme ? {
      ''       => [ "${puppi::params::general_base_source}/puppi/${my_project}/info/readme/readme",
                    "${puppi::params::general_base_source}/puppi/info/readme/readme" ],
      default  => "${readme}" ,
    },
    tag     => 'puppi_info',
  }

  if $bool_autoreadme == true {
  file { "${puppi::params::readmedir}/${name}-custom":
    ensure  => present,
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_readmedir'],
    source  => [  "${puppi::params::general_base_source}/puppi/${my_project}/info/readme/readme--${hostname}" ,
                  "${puppi::params::general_base_source}/puppi/${my_project}/info/readme/readme-${role}" ,
                  "${puppi::params::general_base_source}/puppi/${my_project}/info/readme/readme-default" ,
                  "${puppi::params::general_base_source}/puppi/info/readme/readme--${hostname}" ,
                  "${puppi::params::general_base_source}/puppi/info/readme/readme-${role}" ,
                  "${puppi::params::general_base_source}/puppi/info/readme/readme-default" ],
    tag     => 'puppi_info',
    }
  }

  Puppi::Info::Readme[$name] -> Class['puppi::is_installed']

}
