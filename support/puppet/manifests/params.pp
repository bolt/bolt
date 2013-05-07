#
# Tweak these variables to adjust your development environment:
#
class params {
    # Hostname of the virtualbox (make sure this URL points to 127.0.0.1 on your local dev system!)
    $host = 'www.bolt.dev'

    # Original port (don't change)
    $port = '80'

    # Database names (must match the ones in your app/config/config.yml file)
    $dbname = 'bolt'
    $dbuser = 'bolt'
    $dbpass = 'bolt'

    $filepath = '/vagrant/support/puppet/modules'

    $phpmyadmin = true
}
