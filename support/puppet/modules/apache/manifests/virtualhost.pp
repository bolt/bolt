# = Define: apache::virtualhost
#
# Basic Virtual host management define
# You can use different templates for your apache virtual host files
# Default is virtualhost.conf.erb, adapt it to your needs or create
# your custom template.
#
# == Usage:
# With standard template:
# apache::virtualhost    { "www.example42.com": }
#
# With custom template (create it in MODULEPATH/apache/templates/virtualhost/)
# apache::virtualhost { "webmail.example42.com":
#   templatefile => "webmail.conf.erb"
# }
#
# With custom template in custom location
# (MODULEPATH/mymod/templates/apache/vihost/)
# apache::virtualhost { "webmail.example42.com":
#   templatefile => "webmail.conf.erb"
#   templatepath => "mymod/apache/vihost"
# }
#
define apache::virtualhost (
  $templatefile   = 'virtualhost.conf.erb' ,
  $templatepath   = 'apache/virtualhost' ,
  $documentroot   = '' ,
  $filename       = '' ,
  $aliases        = '' ,
  $create_docroot = true ,
  $enable         = true ,
  $owner          = '' ,
  $groupowner     = '' ) {

  include apache

  $real_filename = $filename ? {
    ''      => $name,
    default => $filename,
  }

  $real_documentroot = $documentroot ? {
    ''      =>  "${apache::data_dir}/${name}",
    default => $documentroot,
  }

  $real_owner = $owner ? {
        ''      => $apache::config_file_owner,
        default => $owner,
  }

  $real_groupowner = $groupowner ? {
        ''      => $apache::config_file_group,
        default => $groupowner,
}

  $real_path = $::operatingsystem ? {
    /(?i:Debian|Ubuntu|Mint)/ => "${apache::vdir}/${real_filename}",
    default                   => "${apache::vdir}/${real_filename}.conf",
  }

  $ensure_link = any2bool($enable) ? {
    true  => "${apache::vdir}/${real_filename}",
    false => absent,
  }
  $ensure = bool2ensure($enable)
  $bool_create_docroot = any2bool($create_docroot)

  file { "ApacheVirtualHost_$name":
    ensure  => $ensure,
    path    => $real_path,
    content => template("${templatepath}/${templatefile}"),
    mode    => $apache::config_file_mode,
    owner   => $apache::config_file_owner,
    group   => $apache::config_file_group,
    require => Package['apache'],
    notify  => $apache::manage_service_autorestart,
  }

  # Some OS specific settings:
  # On Debian/Ubuntu manages sites-enabled
  case $::operatingsystem {
    ubuntu,debian,mint: {
      file { "ApacheVirtualHostEnabled_$name":
        ensure  => $ensure_link,
        path    => "${apache::config_dir}/sites-enabled/${real_filename}",
        require => Package['apache'],
      }
    }
    redhat,centos,scientific,fedora: {
      include apache::redhat
    }
    default: { }
  }

  if $bool_create_docroot == true {
    file { $real_documentroot:
      ensure => directory,
      owner  => $real_owner,
      group  => $real_groupowner,
      mode   => '0775',
    }
  }

}
