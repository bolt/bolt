require 'spec_helper'

describe "Facter::Util::Fact iptables_persistent_version" do
  before { Facter.clear }
  let(:dpkg_cmd) { "dpkg-query -Wf '${Version}' iptables-persistent 2>/dev/null" }

  {
    "Debian" => "0.0.20090701",
    "Ubuntu" => "0.5.3ubuntu2",
  }.each do |os, ver|
    describe "#{os} package installed" do
      before {
        Facter.fact(:operatingsystem).stubs(:value).returns(os)
        Facter::Util::Resolution.stubs(:exec).with(dpkg_cmd).returns(ver)
      }
      it { Facter.fact(:iptables_persistent_version).value.should == ver }
    end
  end

  describe 'Ubuntu package not installed' do
    before {
      Facter.fact(:operatingsystem).stubs(:value).returns("Ubuntu")
      Facter::Util::Resolution.stubs(:exec).with(dpkg_cmd).returns(nil)
    }
    it { Facter.fact(:iptables_persistent_version).value.should be_nil }
  end

  describe 'CentOS not supported' do
    before { Facter.fact(:operatingsystem).stubs(:value).returns("CentOS") }
    it { Facter.fact(:iptables_persistent_version).value.should be_nil }
  end
end
