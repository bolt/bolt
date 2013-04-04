#!/bin/bash
# get_maven_files.sh - Made for Puppi
# This script retrieves the files to deploy from a Maven repository.
# It uses variables defined in the general and project runtime configuration files.
# It uses curl to retrieve files so the $1 argument (base url of the maven repository) 
# has to be in curl friendly format

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Obtain the value of the variable with name passed as second argument
# If no one is given, we take all the files in storedir

#echo "Download and deploy $2 ? (Y/n)" 
#read press
#case $press in 
#    Y|y) true ;;
#    N|n) save_runtime_config "predeploydir_$2=" ; exit 0
#esac

if [ $debug ] ; then
    tarcommand="tar -xvf"
else
    tarcommand="tar -xf"
fi

cd $storedir
case $2 in
    warfile)
        curl -s -f $1/$version/$warfile -O
        check_retcode
        cp -a $warfile $predeploydir/$artifact.war
        save_runtime_config "deploy_warpath=$deploy_root/$artifact.war"
    ;;
    jarfile)
        curl -s -f $1/$version/$jarfile -O
        check_retcode
        cp -a $jarfile $predeploydir/$artifact.jar
        save_runtime_config "deploy_jarpath=$deploy_root/$artifact.jar"
    ;;
    configfile)
        curl -s -f $1/$version/$configfile -O
        check_retcode
        mkdir /tmp/puppi/$project/deploy_configfile
        cd /tmp/puppi/$project/deploy_configfile
        $tarcommand $storedir/$configfile
        check_retcode
        save_runtime_config "predeploydir_configfile=/tmp/puppi/$project/deploy_configfile"
    ;;
    srcfile)
        curl -s -f $1/$version/$srcfile -O
        check_retcode
        mkdir /tmp/puppi/$project/deploy_srcfile
        cd /tmp/puppi/$project/deploy_srcfile
        $tarcommand $storedir/$srcfile
        check_retcode
        save_runtime_config "predeploydir_srcfile=/tmp/puppi/$project/deploy_srcfile"
    ;;
esac
