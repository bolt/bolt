require 'rake'
require 'rspec/core/rake_task'

RSpec::Core::RakeTask.new(:test) do |t|
  t.rspec_opts = ["--format", "doc", "--color"]
  t.pattern = 'spec/*/*_spec.rb'
end

task :default => :test
