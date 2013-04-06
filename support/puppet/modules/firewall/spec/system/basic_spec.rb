require 'spec_helper_system'

# TODO: we probably wanna break this into pieces
describe "basic tests:" do
  # This helper flushes all tables on the default machine.
  #
  # It checks that the flush command returns with no errors.
  def iptables_flush_all_tables
    ['filter', 'nat', 'mangle', 'raw'].each do |t|
      system_run("iptables -t #{t} -F") do |s, o, e|
        s.exitstatus.should == 0
        e.should == ''
      end
    end
  end

  context 'prelim:' do
    it 'make sure we have copied the module across' do
      # No point diagnosing any more if the module wasn't copied properly
      system_run("ls /etc/puppet/modules/firewall") do |s, o, e|
        s.exitstatus.should == 0
        o.should =~ /Modulefile/
        e.should == ''
      end
    end
  end

  context 'puppet resource firewall command:' do
    it 'make sure it returns no errors when executed on a clean machine' do
      # Except for the absence of iptables, it should run perfectly usually
      # most hosts have iptables at least.
      system_run('puppet resource firewall') do |s, o, e|
        s.exitstatus.should == 0
        # don't check stdout, some boxes come with rules, that is normal
        e.should == ''
      end
    end

    it 'flush iptables and make sure it returns nothing afterwards' do
      iptables_flush_all_tables
      # No rules, means no output thanks. And no errors as well.
      system_run('puppet resource firewall') do |s, o, e|
        s.exitstatus.should == 0
        e.should == ''
        o.should == "\n"
      end
    end
  end
end
