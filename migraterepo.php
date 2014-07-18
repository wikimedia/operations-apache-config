<?php

// Script for migrating changes between repos
// Copy and run script from working copy of operations/puppet.git in modules/mediawiki/files/apache/sites folder

$file = file_get_contents( 'https://gerrit.wikimedia.org/r/changes/?q=status:open+project:operations/apache-config+owner:Reedy' );

// Remove cruft from start of file
$file = str_replace( ")]}'", '', $file );
$json = json_decode( $file );

//var_dump( $json );

$count = 0;
foreach( $json as $j ) {
	if( strpos( $j->subject, 'mod_proxy_fcgi' ) === false ) {
		continue;
	}
	$changeid = $j->_number;
	$end = substr( $changeid, -2 );
	// TODO: Only works for patchset 1
	exec( "git fetch https://gerrit.wikimedia.org/r/operations/apache-config refs/changes/$end/$changeid/1 && git cherry-pick FETCH_HEAD && git review && git reset HEAD~1 --hard" );

	$count++;
}

echo $count . "\n";

/**
  {
    "kind": "gerritcodereview#change",
    "id": "operations%2Fapache-config~master~I9671e6df30db7718ec8329b00f46110352ddf203",
    "project": "operations/apache-config",
    "branch": "master",
    "topic": "votehhvm",
    "change_id": "I9671e6df30db7718ec8329b00f46110352ddf203",
    "subject": "Apache config for votewiki using mod_proxy_fcgi",
    "status": "NEW",
    "created": "2014-07-14 21:47:25.000000000",
    "updated": "2014-07-14 21:47:30.000000000",
    "mergeable": true,
    "_sortkey": "002e6d1b00023b74",
    "_number": 146292,
    "owner": {
      "name": "Reedy"
    }
  },
*/
