class bolt::prepare {

    # download composer if it's not there yet
    exec { "getcomposer":
        command => "/usr/bin/curl -sS https://getcomposer.org/installer | php",
        cwd => "/vagrant/",
        creates => "/vagrant/composer.phar",
        require => [ Package["curl"] ],
        timeout => 0,
        tries => 3,
    }

    exec { "composerupdate":
        command => "/usr/bin/php /vagrant/composer.phar self-update",
        cwd => "/vagrant/",
        require => [ Exec["getcomposer"]],
        timeout => 0,
        tries => 3,
    }

    # Install / Update the vendors
    exec { "vendorupdate" :
        command => "/usr/bin/php /vagrant/composer.phar install",
        cwd => "/vagrant/",
        creates => "/vagrant/vendor/silex",
        require => [ Package["php"], Package["git"], Exec["composerupdate"]],
        timeout => 0,
        tries => 3,
    }

    # Clear cache dir, use nut for this
    exec { "clearcache" :
        command => "/usr/bin/php /vagrant/app/nut cache:clear",
        cwd => "/vagrant/",
        require => [ Package["php"], Exec["vendorupdate"]],
        timeout => 0,
        tries => 3,
    }

}