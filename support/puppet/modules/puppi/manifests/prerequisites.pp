# Class puppi::prerequisites
#
# This class provides commands and tools needed for full Puppi
# functionality. Since you might already have these package
# resources in your modules, to avoid conflicts you may decide
# to include the needed packages somewhere else and avoid the
# direct inclusion of puppi::prerequisites
#
class puppi::prerequisites {

  require puppi::params

  if ! defined(Package['curl']) {
    package { 'curl' : ensure => present }
  }

  if ! defined(Package['unzip']) {
    package { 'unzip' : ensure => present }
  }

  if ! defined(Package['rsync']) {
    package { 'rsync' : ensure => present }
  }

  if ! defined(Package[$puppi::params::package_nagiosplugins]) {
    package { $puppi::params::package_nagiosplugins : ensure => present }
  }

  if ! defined(Package[$puppi::params::package_mail]) {
    package { $puppi::params::package_mail : ensure => present }
  }

  Class['puppi::prerequisites'] -> Class['puppi::is_installed']

}
