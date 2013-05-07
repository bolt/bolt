#!/bin/bash
set +x
# report_mongodb.sh - Made for Puppi
# This script sends a summary to a mongodb defined in $1
# e.g. somemongohost/dbname

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10


if [ "$EXITCRIT" = "1" ] ; then
    proposed_exit=2
fi

if [ "$EXITWARN" = "1" ] ; then
    proposed_exit=1
fi

# check prerequisites
mongo -version > /dev/null
if [ $? -ne 0 ]; then
        echo "mongo-client is not installed, aborting"
        exit $proposed_exit
fi

fqdn=$(facter fqdn)

environment=$(facter environment -p)

# something like mongodb://someuser:hispassword@somehost/somedb
mongourl=$1

if [[ ! $mongourl =~ "mongodb://" ]]; then
  echo "WARNING: mongourl invalid! Please use a valid monurl!"
  exit $proposed_exit
fi

if [[ $mongourl =~ @ ]]; then
  # ok we have to deal with passwords
  # you HAVE to provide a password if you provide a user
  mongodb=`echo $mongourl | sed 's/.*@//'`
  mongouser=`echo $mongourl | sed 's/mongodb:\/\///' | sed 's/:.*//' `
  mongopassword=`echo $mongourl | sed 's/mongodb:\/\///' | sed 's/[^:]*://' | sed 's/@.*//' `
  mongoarguments="--username $mongouser --password $mongopassword"
else
  mongodb=`echo $mongourl | sed 's/mongodb:\/\///'` 	
fi

result=$(grep result $logdir/$project/$tag/summary | awk '{ print $NF }')
summary=$(cat $logdir/$project/$tag/summary)

mcmd="db.deployments.insert({ts:new Date(),result:\"${result}\",fqdn:\"${fqdn}\",project:\"${project}\",source:\"${source}\",tag:\"${tag}\",version:\"${version}\",artifact:\"${artifact}\",testmode:\"${testmode}\",warfile:\"${warfile}\",environment:\"${environment}\"}); quit(0)"


mongo $mongoarguments $mongodb --eval "$mcmd"

# Now do a reporting to enable "most-recent-versions on all servers"

read -r -d '' mcmd <<'EOF'
var map = function() {
  project=this.project ;
  emit( this.fqdn +":"+ this.project,  {project:this.project, fqdn:this.fqdn, ts:this.ts,version:this.version,environment:this.environment}  );
};
var reduce = function(k,vals) {
  result = vals[0];
  vals.forEach(function(val) { if (val.ts > result.ts) result=val } ) ;
  return result;
};
db.deployments.mapReduce(
  map,
  reduce,
  {out:{replace:"versions"}})
EOF

mongo $mongoarguments $mongodb --eval "$mcmd"

exit $proposed_exit
