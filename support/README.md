Vagrant and Puppet setup
========================

Vagrant makes it easy to create a virtual environment in which you can run your code. The benefit of this is that people who are using it are sure to be using the same environment. This means that bugs can be easily reproduced as the environment cannot be the issue.

Vagrant? Puppet? Oh noes.. I don't know it, what now?
-----------------------------------------------------

Note: you don't have to use it. However it can be handy to use as you don't need to set up your local machine and may screw up your configuration for (or by) an other project you're working on. Using it is made as easy as possible.

Using the setup
---------------

### Prerequisites
- [Vagrant](http://www.vagrantup.com)
- [VirtualBox](https://www.virtualbox.org)

### Commands
There are just a couple of commands to remember (yes, vagrant is a command line tool):

- To boot the virtual environment: `$ vagrant up` 
- To throw away the virtual environment: `$ vagrant destroy`
- To pause the virtual environment: `$ vagrant suspend`
- To resume the virtual environment: `$ vagrant resume`
- To view the status of your VMs: `$ vagrant status`
- To SSH into the virtual machine: `$ vagrant ssh`

Note that you need to execute these commands in the `bolt` folder.

Basically you'd use `vagrant up` if there's no VM yet, use `vagrant resume` if it's suspended and use `vagrant suspend` when you're done developing at that point.

I did a vagrant up, all that output! What just happened?
--------------------------------------------------------

Most of the output comes from puppet. Vagrant creates a virtual machine (or VM) with a particular distribution. In this case, it's CentOS 6.3, 64 bit.
After that, the puppet scripts make sure that all stuff you need (like a web server, php, mysql etc.) is installed.
Another thing that vagrant does is share your bolt directory with that server. That means that every thing you do in your local IDE is automatically available in your VM.

Why didn't you use submodules?
------------------------------
We just need a working environment. Submodules increase the possibility that stuff breaks or becomes incompatible. We don't want to be supporting all kind of submodule related issues at this moment. That's why modules are included at this moment and might be changed later on.