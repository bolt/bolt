require 'spec_helper'

describe 'firewall', :type => :class do
  context 'kernel => Linux' do
    let(:facts) {{ :kernel => 'Linux' }}
    it { should include_class('firewall::linux') }
  end
end
