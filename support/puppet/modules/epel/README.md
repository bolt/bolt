# Configure EPEL (Extra Repository for Enterprise Linux)

# About
This module basically just mimics the epel-release rpm. The same repos are
enabled/disabled and the GPG key is imported.  In the end you will end up with
the EPEL repos configured.

The following Repos will be setup and enabled by default:

  * epel

Other repositories that will setup but disabled (as per the epel-release setup)

  * epel-debuginfo
  * epel-source
  * epel-testing
  * epel-testing-debuginfo
  * epel-testing-source

# Proxy
If you have an http proxy required to access the internet, you can use the
$proxy variable in the params.pp file. If it is set to a value other than
'absent' a proxy will be setup with each repository.  Note that otherwise each
of the repos will fall back to settings in the /etc/yum.conf file.

# Why?
I am a big fan of EPEL. I actually was one of the people who helped get it
going. I am also the owner of the epel-release package, so in general this
module should stay fairly up to date with the official upstream package.

I just got sick of coding Puppet modules and basically having an assumption
that EPEL was setup or installed.  I can now depend on this module instead.

I realize it is fairly trivial to get EPEL setup. Every now-and-then however
the path to epel-release changes because something changes in the package (mass
rebuild, rpm build macros updates, etc).  This  module will bypass the changing
URL and just setup the package mirrors.

This does mean that if you are looking for RPM macros that are normally
included with EPEL release, this will not have them.

# Futher Information

* [EPEL Wiki](http://fedoraproject.org/wiki/EPEL)
* [epel-release package information](http://mirrors.servercentral.net/fedora/epel/6/i386/repoview/epel-release.html)

# Testing

* This was tested using Puppet 2.7.x on Centos5/6
* I assume it will work on any RHEL variant
* Also, I think this should work with earlier versions of Puppet (2.6.x at least)

# License
Apache Software License 2.0
