require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::run' do

  let(:title) { 'myapp' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 
      'project'  =>  'myapp',
    }
  }

  describe 'Test puppi run exe creation' do
    it 'should create a puppi::run exec' do
      content = catalogue.resource('exec', 'Run_Puppi_myapp').send(:parameters)[:command]
      content.should match /puppi deploy myapp/
    end
  end

end
