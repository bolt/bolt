# This helper file is specific to the system tests for puppetlabs-firewall
# and should be included by all tests under spec/system
require 'rspec-system/spec_helper'
require 'rspec-system-puppet/helpers'

RSpec.configure do |c|
  # Project root for the firewall code
  proj_root = File.expand_path(File.join(File.dirname(__FILE__), '..'))

  # This is where we 'setup' the nodes before running our tests
  c.system_setup_block = proc do
    # TODO: find a better way of importing this into this namespace
    include RSpecSystemPuppet::Helpers

    # Install puppet
    system_puppet_install

    # Copy this module into the module path of the test node
    system_puppet_module_from_path(:source => proj_root, :module_name => 'firewall')
  end
end
