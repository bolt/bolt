# Define: php::pecl::config
#
# Configures pecl
#
# Usage:
# php::pecl::config { http_proxy: value => "myproxy:8080" }
#
define php::pecl::config ($value) {

    include php::pecl

    exec { "pecl-config-set-${name}":
            command => "pecl config-set ${name} ${value}",
            unless  => "pecl config-get ${name} | grep ${value}",
            require => Package["php-pear"],
    }

}

