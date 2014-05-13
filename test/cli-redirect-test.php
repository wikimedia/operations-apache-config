<?php

while (( $line = readline( '> ' ) ) !== false ) {
    $url = trim( $line );
    if ( !preg_match( '/^http:\/\//', $url ) ) {
        $url = 'http://' . $url;
    }
    $c = curl_init( $url );
    curl_setopt_array( $c, array(
        CURLOPT_HEADER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_PROXY => 'localhost:8999',
        CURLOPT_USERAGENT => 'curl',
    ) );
    $result = curl_exec( $c );
    $info = curl_getinfo( $c );
    if ( $info['http_code'] == 301 || $info['http_code'] == 302 ) {
        $m = false;
        preg_match( '/Location: (.*)\\n/', $result, $m );
        echo "-> {$m[1]}\n";
    } else {
        echo $info['http_code'] . "\n";
        if ( $info['http_code'] == 403 ) {
            echo $result;
        }
    }
    readline_add_history( $line );
}
