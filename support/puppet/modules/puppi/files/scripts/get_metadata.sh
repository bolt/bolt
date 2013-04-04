#!/bin/bash
# get_metadata.sh - Made for Puppi

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10

# Show help
showhelp () {
    echo "This script retrieves some metadata from the downwloaded files "
    echo "The metadatasource is automatically detected from the \$source_type runtime config"
    echo
    echo "It has some, not required, options:"
    echo "-m <magicstring> - The string to use as *fix in custom metadata info provided "
    echo "-mc <anotherstring> - The string to use as qualifier for Maven metadata config tars"
    echo "-mj <anotherstring> - The string to use as qualifier for Maven metadata jars"
}

while [ $# -gt 0 ]; do
  case "$1" in
    -m)
      suffix=$2
      shift 2 ;;
    -mc)
      config_suffix=$2
      shift 2 ;;
    -mj)
      jar_suffix=$2
      shift 2 ;;
    -h)
      showhelp ;;
  esac
done


case $source_type in
    list)
    if [ -z $suffix ] ; then
        suffix="####"
    fi
    # TODO Make this more secure, for God's sake!
    for param in $(cat $downloadedfile | grep "^$suffix" ) ; do
        save_runtime_comment $param
    done
    ;;
    tarball)
    ;;
    maven)
    [ ${#version} -eq 0 ] && version=$(xml_parse release $downloadedfile )
    artifact=$(xml_parse artifactId $downloadedfile )
    warfile=$artifact-$version.war

    # Definition of qualifiers for Maven has changed from the (wrong) assumption
    # of having cfg-$suffix and src-$suffix for staticfiles and config tarballs
    # to a more flexible management of qualifiers names with two different params (-m and -mc)
    # The "suffixnotset" string is passed by default by the Puppi maven define
    # YES, it's crap. 
    if [[ x$suffix != "xsuffixnotset" ]] ; then
        srcfile=$artifact-$version-$suffix.tar
    else 
        srcfile=$artifact-$version.tar
    fi

    if [[ x$config_suffix != "xsuffixnotset" ]] ; then
        configfile=$artifact-$version-$config_suffix.tar
    else
        configfile=$artifact-$version.tar
    fi

    if [[ x$jar_suffix != "xsuffixnotset" ]] ; then
        jarfile=$artifact-$version-$jar_suffix.jar
    else
        jarfile=$artifact-$version.jar
    fi

    # Store metadata
    save_runtime_config "version=$version" 
    save_runtime_config "artifact=$artifact"
    # Store filenames
    save_runtime_config "warfile=$warfile"
    save_runtime_config "jarfile=$jarfile"
    save_runtime_config "srcfile=$srcfile" 
    save_runtime_config "configfile=$configfile" 
    ;;
esac

