# Class apache::redhat
#
# Apache resources specific for RedHat
#
class apache::redhat {
  apache::dotconf { '00-NameVirtualHost':
    content => template('apache/00-NameVirtualHost.conf.erb'),
  }
}
