# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant::Config.run do |config|
    # All Vagrant configuration is done here. The most common configuration
    # options are documented and commented below. For a complete reference,
    # please see the online documentation at vagrantup.com.

    # This vagrant will be running on centos 6.3, 32bit with Puppet provisioning
    config.vm.box = 'centos-63-32-puppet'
    config.vm.box_url = 'https://dl.dropbox.com/sh/9rldlpj3cmdtntc/chqwU6EYaZ/centos-63-32bit-puppet.box'

    # The url from where the 'config.vm.box' box will be fetched if it
    # doesn't already exist on the user's system.

    # Boot with a GUI so you can see the screen. (Default is headless)
    config.vm.boot_mode = :gui

    config.vm.define :bolt do |bolt_config|
        config.vm.host_name = "www.bolt.dev"
        #config.vm.share_folder "v-data", ".", "/vagrant/app/cache", :owner => "www-data", :group => "www-data"

        # Assign this VM to a host-only network IP, allowing you to access it
        # via the IP. Host-only networks can talk to the host machine as well as
        # any other machines on the same network, but cannot be accessed (through this
        # network interface) by any external networks.
        config.vm.network :hostonly, "33.33.33.10"

        # Assign this VM to a bridged network, allowing you to connect directly to a
        # network using the host's network device. This makes the VM appear as another
        # physical device on your network.
        # config.vm.network :bridged

        # Forward a port from the guest to the host, which allows for outside
        # computers to access the VM, whereas host only networking does not.
        # config.vm.forward_port 80, 8080

        # Share an additional folder to the guest VM. The first argument is
        # an identifier, the second is the path on the guest to mount the
        # folder, and the third is the path on the host to the actual folder.
        # config.vm.share_folder "v-data", "/vagrant_data", "../data"

        # Pass custom arguments to VBoxManage before booting VM
        bolt_config.vm.customize [
            'modifyvm', :id, '--chipset', 'ich9', # solves kernel panic issue on some host machines
            '--uartmode1', 'file', 'C:\\base6-console.log' # uncomment to change log location on Windows
        ]

        # Let Puppet take care of the rest of the installation procedure
        # The manifest is located in `support/puppet/manifests/bolt.pp`
        bolt_config.vm.provision :puppet do |puppet|
            puppet.manifests_path = "support/puppet/manifests"
            puppet.module_path = "support/puppet/modules"
            puppet.manifest_file = "bolt.pp"
            puppet.options = [
                '--verbose',
                '--debug',
            ]
        end


    # Enable provisioning with Puppet stand alone.  Puppet manifests
    # are contained in a directory path relative to this Vagrantfile.
    # You will need to create the manifests directory and a manifest in
    # the file debian-6-64.pp in the manifests_path directory.
    #
    # An example Puppet manifest to provision the message of the day:
    #
    # # group { "puppet":
    # #   ensure => "present",
    # # }
    # #
    # # File { owner => 0, group => 0, mode => 0644 }
    # #
    # # file { '/etc/motd':
    # #   content => "Welcome to your Vagrant-built virtual machine!
    # #               Managed by Puppet.\n"
    # # }
    #
    # config.vm.provision :puppet do |puppet|
    #   puppet.manifests_path = "manifests"
    #   puppet.manifest_file  = "debian-6-64.pp"
    # end

    end
end