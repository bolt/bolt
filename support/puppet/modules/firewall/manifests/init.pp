# Class: firewall
#
# Manages the installation of packages for operating systems that are
# currently supported by the firewall type.
#
class firewall {
  case $::kernel {
    'Linux': {
      class { "${title}::linux": }
    }
    default: {
      fail("${title}: Kernel '${::kernel}' is not currently supported")
    }
  }
}
