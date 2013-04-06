# Class epel
#
# Actions:
#   Configure the proper repositories and import GPG keys
#
# Reqiures:
#   You should probably be on an Enterprise Linux variant. (Centos, RHEL, Scientific, Oracle, Ascendos, et al)
#
# Sample Usage:
#  include epel
#
class epel inherits epel::params {

  if $::osfamily == 'RedHat' and $::operatingsystem != 'Fedora' {

    yumrepo { 'epel-testing':
      baseurl        => "http://download.fedora.redhat.com/pub/epel/testing/${::os_maj_version}/${::architecture}",
      failovermethod => 'priority',
      proxy          => $epel::params::proxy,
      enabled        => '0',
      gpgcheck       => '1',
      gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${::os_maj_version}",
      descr          => "Extra Packages for Enterprise Linux ${::os_maj_version} - Testing - ${::architecture} "
    }

    yumrepo { 'epel-testing-debuginfo':
      baseurl        => "http://download.fedora.redhat.com/pub/epel/testing/${::os_maj_version}/${::architecture}/debug",
      failovermethod => 'priority',
      proxy          => $epel::params::proxy,
      enabled        => '0',
      gpgcheck       => '1',
      gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${::os_maj_version}",
      descr          => "Extra Packages for Enterprise Linux ${::os_maj_version} - Testing - ${::architecture} - Debug"
    }

    yumrepo { 'epel-testing-source':
      baseurl        => "http://download.fedora.redhat.com/pub/epel/testing/${::os_maj_version}/SRPMS",
      failovermethod => 'priority',
      proxy          => $epel::params::proxy,
      enabled        => '0',
      gpgcheck       => '1',
      gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${::os_maj_version}",
      descr          => "Extra Packages for Enterprise Linux ${::os_maj_version} - Testing - ${::architecture} - Source"
    }

    yumrepo { 'epel':
      mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-${::os_maj_version}&arch=${::architecture}",
      failovermethod => 'priority',
      proxy          => $epel::params::proxy,
      enabled        => '1',
      gpgcheck       => '1',
      gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${::os_maj_version}",
      descr          => "Extra Packages for Enterprise Linux ${::os_maj_version} - ${::architecture}"
    }

    yumrepo { 'epel-debuginfo':
      mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-debug-${::os_maj_version}&arch=${::architecture}",
      failovermethod => 'priority',
      proxy          => $epel::params::proxy,
      enabled        => '0',
      gpgcheck       => '1',
      gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${::os_maj_version}",
      descr          => "Extra Packages for Enterprise Linux ${::os_maj_version} - ${::architecture} - Debug"
    }

    yumrepo { 'epel-source':
      mirrorlist     => "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-source-${::os_maj_version}&arch=${::architecture}",
      proxy          => $epel::params::proxy,
      failovermethod => 'priority',
      enabled        => '0',
      gpgcheck       => '1',
      gpgkey         => "file:///etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${::os_maj_version}",
      descr          => "Extra Packages for Enterprise Linux ${::os_maj_version} - ${::architecture} - Source"
    }

    file { "/etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${::os_maj_version}":
      ensure => present,
      owner  => 'root',
      group  => 'root',
      mode   => '0644',
      source => "puppet:///modules/epel/RPM-GPG-KEY-EPEL-${::os_maj_version}",
    }

    epel::rpm_gpg_key{ "EPEL-${::os_maj_version}":
      path => "/etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-${::os_maj_version}"
    }
  } else {
      notice ("Your operating system ${::operatingsystem} will not have the EPEL repository applied")
  }

}
