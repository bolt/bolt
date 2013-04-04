# Class: puppi::params
#
# Sets internal variables and defaults for puppi module
#
class puppi::params  {

## INTERNALVARS

    $basedir     = '/etc/puppi'
    $scriptsdir  = '/etc/puppi/scripts'
    $checksdir   = '/etc/puppi/checks'
    $logsdir     = '/etc/puppi/logs'
    $infodir     = '/etc/puppi/info'
    $tododir     = '/etc/puppi/todo'
    $projectsdir = '/etc/puppi/projects'
    $datadir     = '/etc/puppi/data'
    $helpersdir  = '/etc/puppi/helpers'
    $workdir     = '/tmp/puppi'
    $libdir      = '/var/lib/puppi'
    $archivedir  = '/var/lib/puppi/archive'
    $readmedir   = '/var/lib/puppi/readme'
    $logdir      = '/var/log/puppi'

    $configfile_mode  = '0644'
    $configfile_owner = 'root'
    $configfile_group = 'root'

# External tools
# Directory where are placed the checks scripts
# By default we use Nagios plugins
    $checkpluginsdir = $::operatingsystem ? {
        /(?i:RedHat|CentOS|Scientific|Amazon|Linux)/ => $::architecture ? {
            x86_64  => '/usr/lib64/nagios/plugins',
            default => '/usr/lib/nagios/plugins',
        },
        default     => '/usr/lib/nagios/plugins',
    }

    $package_nagiosplugins = $operatingsystem ? {
        /(?i:RedHat|CentOS|Scientific|Amazon|Linux|Fedora)/ => 'nagios-plugins-all',
        default                                             => 'nagios-plugins',
    }

    $package_mail = $operatingsystem ? {
        /(?i:Debian|Ubuntu|Mint)/ => 'bsd-mailx',
        default                   => 'mailx',
    }

    $ntp = $ntp_server ? {
        ''      => 'pool.ntp.org' ,
        default => $ntp_server ,
    }

# Mcollective paths
# TODO: Add Paths for Pupept Enterprise:
# /opt/puppet/libexec/mcollective/mcollective/

    $mcollective = $::operatingsystem ? {
        debian  => "/usr/share/mcollective/plugins/mcollective",
        ubuntu  => "/usr/share/mcollective/plugins/mcollective",
        centos  => "/usr/libexec/mcollective/mcollective",
        redhat  => "/usr/libexec/mcollective/mcollective",
        default => "/usr/libexec/mcollective/mcollective",
    }

    $mcollective_user = 'root'
    $mcollective_group = 'root'


# Commands used in puppi info templates

    $info_package_query = $::operatingsystem ? {
        /(?i:RedHat|CentOS|Scientific|Amazon|Linux)/ => 'rpm -qi',
        /(?i:Ubuntu|Debian|Mint)/       => 'dpkg -s',
        default                         => 'echo',
    }

    $info_package_list = $::operatingsystem ? {
        /(?i:RedHat|CentOS|Scientific|Amazon|Linux)/ => 'rpm -ql',
        /(?i:Ubuntu|Debian|Mint)/       => 'dpkg -L',
        default                         => 'echo',
    }

    $info_service_check = $::operatingsystem ? {
        default => '/etc/init.d/',
    }



## FILE SERVING SOURCE
# Sets the correct source for static files
# In order to provide files from different sources without modifying the module
# you can override the default source path setting the variable $base_source
# Ex: $base_source="puppet://ip.of.fileserver" or
# $base_source="puppet://$servername/myprojectmodule"

    case $::base_source {
        '': {
            $general_base_source = $::puppetversion ? {
                /(^0.25)/ => 'puppet:///modules',
                /(^0.)/   => "puppet://$::servername",
                default   => 'puppet:///modules',
            }
        }
        default: { $general_base_source=$::base_source }
    }

    Class['puppi::params'] -> Class['puppi::is_installed']

}
