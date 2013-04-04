require "#{File.join(File.dirname(__FILE__),'..','spec_helper.rb')}"

describe 'puppi::helper' do

  let(:title) { 'standard' }
  let(:node) { 'rspec.example42.com' }
  let(:params) {
    { 'template'   => 'puppi/helper/standard.yaml.erb'  }
  }

  describe 'Test puppi helper file creation' do
    it 'should create a puppi helper file' do
      should contain_file('puppi_helper_standard').with_ensure('present')
    end
    it 'should populate correctly the helper file' do
      content = catalogue.resource('file', 'puppi_helper_standard').send(:parameters)[:content]
      content.should match('info:')
    end
  end

end
