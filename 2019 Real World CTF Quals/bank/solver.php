<?php

$stream = stream_socket_client("tcp://tcp.realworldctf.com:20014/ ", $errno, $errstr);
// $stream = stream_socket_client("tcp://localhost:20014/ ", $errno, $errstr);

if (!$stream) {
    echo "{$errno}: {$errstr}\n";
    die();
}

//////////////////////////////////////
// init

$proof_msg = fgets($stream);
echo $proof_msg;
$proof_prefix = array_values(array_slice(explode(' ', trim($proof_msg)), -1))[0];

$proof = exec("python solver_helper.py 1 $proof_prefix");
echo "$proof\n";
fwrite($stream, $proof);

echo fgets($stream);

//////////////////////////////////////
// public key

$public_key = [
    '55066263022277343669578718895168534326250603453777594175500187360389116729240',
    '32670510020758816978083085130507043184471273380659243275938904335757337482424'
];
send_public_key($stream, $public_key);

//////////////////////////////////////
// server key

fwrite($stream, base64_encode('3')."\n");
echo "3\n";
echo fgets($stream);
echo fgets($stream);
$srv_key_msg = fgets($stream);
echo $srv_key_msg;
$srv_key = array_values(array_slice(explode(' ', trim($srv_key_msg)), -2));
$srv_key[0] = trim($srv_key[0], '(),L');
$srv_key[1] = trim($srv_key[1], '(),L');
echo "=====\n";
print_r($srv_key);
echo "=====\n";

// $proof = exec("python solver_helper.py 1 $proof_prefix");
// echo "$proof\n";
// fwrite($stream, $proof);

//////////////////////////////////////
// public key

send_public_key($stream, $public_key);

//////////////////////////////////////
// deposit

fwrite($stream, base64_encode('1')."\n");
echo "1\n";

$deposit_sig = exec("python solver_helper.py 2 DEPOSIT");

fwrite($stream, "$deposit_sig\n");
echo "$deposit_sig\n";

echo fgets($stream);

//////////////////////////////////////
// public key attack

$new_public_key = exec("python solver_helper.py 3 {$public_key[0]} {$public_key[1]} {$srv_key[0]} {$srv_key[1]}");
$new_public_key = explode(',', $new_public_key, 2);
send_public_key($stream, $new_public_key);

//////////////////////////////////////
// withdraw

fwrite($stream, base64_encode('2')."\n");
echo "2\n";

$withdraw_sig = exec("python solver_helper.py 2 WITHDRAW");

fwrite($stream, "$withdraw_sig\n");
echo "$withdraw_sig\n";

echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);
echo fgets($stream);




//////////////////////////////////////
// end

//stream_socket_shutdown($stream, STREAM_SHUT_WR); /* This is the important line */
//fclose($stream);

function send_public_key($stream, $public_key)
{
    $public_key_str = $public_key[0].','.$public_key[1];
    fwrite($stream, base64_encode($public_key_str)."\n");
    echo "$public_key_str\n";
    
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
    echo fgets($stream);
}
