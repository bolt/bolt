# Class puppi::helpers
#
# A class that defines all the default helpers used by Example42
#  modules
#
# == Usage
# Automatically included by Puppi
#
class puppi::helpers {

  # Standard helper for Example42 modules
  puppi::helper { 'standard':
    template => 'puppi/helpers/standard.yaml.erb',
  }

  Class['puppi::helpers'] -> Class['puppi::is_installed']

}
