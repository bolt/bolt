#!/usr/bin/env rspec

require 'spec_helper'
require 'puppet/provider/confine/exists'

describe 'iptables provider detection' do
  let(:exists) {
    Puppet::Provider::Confine::Exists
  }

  before :each do
    # Reset the default provider
    Puppet::Type.type(:firewall).defaultprovider = nil
  end

  it "should default to iptables provider if /sbin/iptables[-save] exists" do
    # Stub lookup for /sbin/iptables & /sbin/iptables-save
    exists.any_instance.stubs(:which).with("/sbin/iptables").
      returns "/sbin/iptables"
    exists.any_instance.stubs(:which).with("/sbin/iptables-save").
      returns "/sbin/iptables-save"

    # Every other command should return false so we don't pick up any
    # other providers
    exists.any_instance.stubs(:which).with() { |value|
      ! ["/sbin/iptables","/sbin/iptables-save"].include?(value)
    }.returns false

    # Create a resource instance and make sure the provider is iptables
    resource = Puppet::Type.type(:firewall).new({
      :name => '000 test foo',
    })
    resource.provider.class.to_s.should == "Puppet::Type::Firewall::ProviderIptables"
  end
end

describe 'iptables provider' do
  let(:provider) { Puppet::Type.type(:firewall).provider(:iptables) }
  let(:resource) {
    Puppet::Type.type(:firewall).new({
      :name  => '000 test foo',
      :action  => 'accept',
    })
  }

  before :each do
    Puppet::Type::Firewall.stubs(:defaultprovider).returns provider
    provider.stubs(:command).with(:iptables_save).returns "/sbin/iptables-save"

    # Stub iptables version
    Facter.fact(:iptables_version).stubs(:value).returns("1.4.2")

    Puppet::Util::Execution.stubs(:execute).returns ""
    Puppet::Util.stubs(:which).with("/sbin/iptables-save").
      returns "/sbin/iptables-save"
  end

  it 'should be able to get a list of existing rules' do
    provider.instances.each do |rule|
      rule.should be_instance_of(provider)
      rule.properties[:provider].to_s.should == provider.name.to_s
    end
  end

  it 'should ignore lines with fatal errors' do
    Puppet::Util::Execution.stubs(:execute).with(['/sbin/iptables-save']).
      returns("FATAL: Could not load /lib/modules/2.6.18-028stab095.1/modules.dep: No such file or directory")

    provider.instances.length.should == 0
  end

  # Load in ruby hash for test fixtures.
  load 'spec/fixtures/iptables/conversion_hash.rb'

  describe 'when converting rules to resources' do
    ARGS_TO_HASH.each do |test_name,data|
      describe "for test data '#{test_name}'" do
        let(:resource) { provider.rule_to_hash(data[:line], data[:table], 0) }

        # If this option is enabled, make sure the parameters exactly match
        if data[:compare_all] then
          it "the parameter hash keys should be the same as returned by rules_to_hash" do
            resource.keys.should =~ data[:params].keys
          end
        end

        # Iterate across each parameter, creating an example for comparison
        data[:params].each do |param_name, param_value|
          it "the parameter '#{param_name.to_s}' should match #{param_value.inspect}" do
            resource[param_name].should == data[:params][param_name]
          end
        end
      end
    end
  end

  describe 'when working out general_args' do
    HASH_TO_ARGS.each do |test_name,data|
      describe "for test data '#{test_name}'" do
        let(:resource) { Puppet::Type.type(:firewall).new(data[:params]) }
        let(:provider) { Puppet::Type.type(:firewall).provider(:iptables) }
        let(:instance) { provider.new(resource) }

        it 'general_args should be valid' do
          instance.general_args.flatten.should == data[:args]
        end
      end
    end
  end

  describe 'when converting rules without comments to resources' do
    let(:sample_rule) {
      '-A INPUT -s 1.1.1.1 -d 1.1.1.1 -p tcp -m multiport --dports 7061,7062 -m multiport --sports 7061,7062 -j ACCEPT'
    }
    let(:resource) { provider.rule_to_hash(sample_rule, 'filter', 0) }
    let(:instance) { provider.new(resource) }

    it 'rule name contains a MD5 sum of the line' do
      resource[:name].should == "9999 #{Digest::MD5.hexdigest(resource[:line])}"
    end
  end

  describe 'when creating resources' do
    let(:instance) { provider.new(resource) }

    it 'insert_args should be an array' do
      instance.insert_args.class.should == Array
    end
  end

  describe 'when modifying resources' do
    let(:instance) { provider.new(resource) }

    it 'update_args should be an array' do
      instance.update_args.class.should == Array
    end
  end

  describe 'when deleting resources' do
    let(:sample_rule) {
      '-A INPUT -s 1.1.1.1 -d 1.1.1.1 -p tcp -m multiport --dports 7061,7062 -m multiport --sports 7061,7062 -j ACCEPT'
    }
    let(:resource) { provider.rule_to_hash(sample_rule, 'filter', 0) }
    let(:instance) { provider.new(resource) }

    it 'resource[:line] looks like the original rule' do
      resource[:line] == sample_rule
    end

    it 'delete_args is an array' do
      instance.delete_args.class.should == Array
    end

    it 'delete_args is the same as the rule string when joined' do
      instance.delete_args.join(' ').should == sample_rule.gsub(/\-A/,
        '-t filter -D')
    end
  end
end
