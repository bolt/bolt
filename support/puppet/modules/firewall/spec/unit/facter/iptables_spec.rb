require 'spec_helper'

describe "Facter::Util::Fact" do
  before {
    Facter.clear
    Facter.fact(:kernel).stubs(:value).returns("Linux")
    Facter.fact(:kernelrelease).stubs(:value).returns("2.6")
  }

  describe 'iptables_version' do
    it {
      Facter::Util::Resolution.stubs(:exec).with('iptables --version').returns('iptables v1.4.7')
      Facter.fact(:iptables_version).value.should == '1.4.7'
    }
  end

  describe 'ip6tables_version' do
    before { Facter::Util::Resolution.stubs(:exec).with('ip6tables --version').returns('ip6tables v1.4.7') }
    it { Facter.fact(:ip6tables_version).value.should == '1.4.7' }
  end
end
