<?php

class RunRedirectTests {
	var $status;

	var $redirectTests = array(
		'http://mediawiki.org/' => 'http://www.mediawiki.org/',
		'https://mediawiki.org/' => 'https://www.mediawiki.org/',
		'http://wikibooks.org/' => 'http://en.wikibooks.org/',
		'http://wikibooks.org/x' => 'http://en.wikibooks.org/x',
		'http://wiktionary.org/' => 'http://www.wiktionary.org/',
		'http://wiktionary.org/x' => 'http://www.wiktionary.org/x',
		'https://wiktionary.org/x' => 'https://www.wiktionary.org/x',
	);

	function execute() {
		$this->status = true;

		$fd = popen( __DIR__ . '/redirect-test-server.sh', 'w' );
		$t = microtime( true );
		do {
			$f = @fsockopen( 'localhost', '8999', $errno, $errstr, 1 );
			if ( !$f ) {
				usleep( 100000 );
			}
		} while ( !$f && microtime( true ) - $t < 5 );
		if ( !$f ) {
			print "Unable to start server\n";
			exit( 1 );
		}

		$pid = trim( file_get_contents( __DIR__ . '/apache2.pid' ) );

		foreach ( $this->redirectTests as $source => $expectedDest ) {
			if ( preg_match( '/^https:/', $source ) ) {
				$protocol = 'https';
				$url = preg_replace( '/^https:/', 'http:', $source );
			} else {
				$url = $source;
				$protocol = 'http';
			}
			$c = curl_init( $url );
			curl_setopt_array( $c, array(
				CURLOPT_HEADER => true,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_PROXY => 'localhost:8999',
				CURLOPT_USERAGENT => 'curl',
				CURLOPT_HTTPHEADER => array('X-Forwarded-Proto: ' . $protocol)
			) );
			$result = curl_exec( $c );
			$info = curl_getinfo( $c );
			if ( $info['http_code'] == 301 || $info['http_code'] == 302 ) {
				if (preg_match( '/Location: (.*)\\r\\n/', $result, $m )) {
					if ( $m[1] == $expectedDest ) {
						$this->success();
					} else {
						$this->fail( $source, $expectedDest, $m[1] );
					}
				} else {
					$this->fail( $source, $expectedDest, "no location header" );
				}
			} else {
				$this->fail( $source, $expectedDest, "Unexpected HTTP code \"{$info['http_code']}\"" );
			}
		}
		print "\nKilling server $pid\n";
		posix_kill( $pid, SIGTERM );
		return $this->status;
	}

	function success() {
		print ".";
	}

	function fail($source, $expected, $got) {
		print "Failed redirect test $source:\n" .
			"Expected: $expected\n" .
			"Got: $got\n\n";
		$this->status = false;
	}
}

$r = new RunRedirectTests;
$status = $r->execute();
if ( $status ) {
	print "All tests passed\n";
	exit( 0 );
} else {
	print "There were failures\n";
	exit( 1 );
}
