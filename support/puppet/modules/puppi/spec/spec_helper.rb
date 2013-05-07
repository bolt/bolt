# Based on https://github.com/puppetlabs/puppetlabs-ntp/blob/master/spec/spec_helper.rb
# Thanks to Ken Barber for advice about http://projects.puppetlabs.com/issues/11191  
require 'puppet'
require 'rspec-puppet'
require 'tmpdir'

RSpec.configure do |c|
  c.before :each do
    @puppetdir = Dir.mktmpdir("puppi")
    manifestdir = File.join(@puppetdir, "manifests")
    Dir.mkdir(manifestdir)
    FileUtils.touch(File.join(manifestdir, "site.pp"))
    Puppet[:confdir] = @puppetdir
  end

  c.after :each do
#    FileUtils.remove_entry_secure(@puppetdir) # This breaks with multiple spec files
    FileUtils.rm_rf(Dir.glob('/tmp/puppi20*') , :secure => true)
  end

  c.module_path = File.join(File.dirname(__FILE__), '../../')
end
