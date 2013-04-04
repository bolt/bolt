# = Define: apache::module
#
# This define installs and configures apache modules
# On Debian and derivatives it places the module config
# into /etc/apache/mods-available.
# On RedHat and derivatives it just creates the configuration file, if
# provided via the templatefile => argument
# If you need to customize the module .conf file,
# add a templatefile with path to the template,
#
# == Parameters
#
# [*ensure*]
#   If to enable/install the module. Default: present
#   Set to absent to disable/remove the module
#
# [*templatefile*]
#   Optional. Location of the template to use to configure
#   the module
#
# [*install_package*]
#   If a module package has to be installed. Default: false
#   Set to true if the module package is not installed by default
#   and you need to install the relevant package
#   In this case the package name is calculated according to the operatingsystem
#   and the ${name} variable.
#   If the autocalculated package name for the module is not
#   correct, you can explicitely set it (using a string different than
#   true or false)
#
# [*notify_service*]
#   If you want to restart the apache service automatically when
#   the module is applied. Default: true
#
# == Examples
# apache::module { 'proxy':
#   templatefile => 'apache/module/proxy.conf.erb',
# }
#
# apache::module { 'bw':
#   install_package => true,
#   templatefile    => 'myclass/apache/bw.conf.erb',
# }
#
# apache::module { 'proxy_html':
#   install_package => 'libapache2-mod-proxy-html',
# }
#
#
define apache::module (
  $ensure          = 'present',
  $templatefile    = '',
  $install_package = false,
  $notify_service  = true ) {

  $manage_service_autorestart = $notify_service ? {
    true    => 'Service[apache]',
    false   => undef,
  }


  if $install_package != false {
    $modpackage_basename = $::operatingsystem ? {
      /(?i:Ubuntu|Debian|Mint)/ => 'libapache2-mod-',
      default                   => 'mod_',
    }

    $real_install_package = $install_package ? {
      true    => "${modpackage_basename}${name}",
      default => $install_package,
    }

    package { "ApacheModule_${name}":
      ensure  => $ensure,
      name    => $real_install_package,
      notify  => $manage_service_autorestart,
      require => Package['apache'],
    }
  }


  if $templatefile != '' {
    $module_conf_path = $::operatingsystem ? {
      /(?i:Ubuntu|Debian|Mint)/ => "${apache::config_dir}/mods-available/${name}.conf",
      default                   => "${apache::config_dir}/conf.d/module_${name}.conf",
    }

    file { "ApacheModule_${name}_conf":
      ensure  => present ,
      path    => $module_conf_path,
      mode    => $apache::configfile_mode,
      owner   => $apache::configfile_owner,
      group   => $apache::configfile_group,
      content => template($templatefile),
      notify  => $manage_service_autorestart,
      require => Package['apache'],
    }
  }


  if $::operatingsystem == 'Debian'
  or $::operatingsystem == 'Ubuntu'
  or $::operatingsystem == 'Mint' {
    case $ensure {
      'present': {
        exec { "/usr/sbin/a2enmod ${name}":
          unless    => "/bin/sh -c '[ -L ${apache::config_dir}/mods-enabled/${name}.load ] && [ ${apache::config_dir}/mods-enabled/${name}.load -ef ${apache::config_dir}/mods-available/${name}.load ]'",
          notify    => $manage_service_autorestart,
          require   => Package['apache'],
        }
      }
      'absent': {
        exec { "/usr/sbin/a2dismod ${name}":
          onlyif    => "/bin/sh -c '[ -L ${apache::config_dir}/mods-enabled/${name}.load ] && [ ${apache::config_dir}/mods-enabled/${name}.load -ef ${apache::config_dir}/mods-available/${name}.load ]'",
          notify    => $manage_service_autorestart,
          require   => Package['apache'],
        }
      }
      default: {
      }
    }
  }

}
