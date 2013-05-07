require 'spec_helper'

describe 'firewall::linux::redhat', :type => :class do
  it { should contain_service('iptables').with(
    :ensure => 'running',
    :enable => 'true'
  )}
end
