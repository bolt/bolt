require 'spec_helper'

describe 'firewall::linux', :type => :class do
  let(:facts_default) {{ :kernel => 'Linux' }}
  it { should contain_package('iptables').with_ensure('present') }

  context 'RedHat like' do
    %w{RedHat CentOS Fedora}.each do |os|
      context "operatingsystem => #{os}" do
        let(:facts) { facts_default.merge({ :operatingsystem => os }) }
        it { should contain_class('firewall::linux::redhat').with_require('Package[iptables]') }
      end
    end
  end

  context 'Debian like' do
    %w{Debian Ubuntu}.each do |os|
      context "operatingsystem => #{os}" do
        let(:facts) { facts_default.merge({ :operatingsystem => os }) }
        it { should contain_class('firewall::linux::debian').with_require('Package[iptables]') }
      end
    end
  end
end
