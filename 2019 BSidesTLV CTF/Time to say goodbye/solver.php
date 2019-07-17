<?php

$uuid = 'd77dbb4b-9678-477f-b970-3b174d2e717d';
$uuid_low = 0x7d; // 6 bits or more
$token_high = 0;

for (;;) {
    if (($token_high % 256) == 0) {
        echo '.';
    }

    $time = time();
    $token_low = (($time ^ $uuid_low) & 0x3F);
    $token_high++;
    if ($token_high > (999999 >> 6)) {
        $token_high = 0;
    }
    $token = ($token_high << 6) | $token_low;

    $url = "http://totp.challenges.bsidestlv.com/validate/$uuid:$token";
    $ret = file_get_contents($url);
    if (!preg_match('/^(\d+): Token mis-match\n\n$/', $ret)) {
        $text = "Time $time, token $token, output: $ret\n\n";
        echo "\n$text";
        file_put_contents('out.txt', $text, FILE_APPEND);
    }
}


/*
$time = microtime(true);
usleep(($time - floor($time)) * 1000000);
$time = ceil($time);

$time_to_wait = 1;
while (($time >> 6) == (($time + $time_to_wait) >> 6)) {
    $time_to_wait++;
}

$url = "http://totp.challenges.bsidestlv.com/getuuid";
$json = file_get_contents($url);
echo $json;
$data = json_decode($json, true);

usleep(($time_to_wait - (microtime(true) - $time)) * 1000000);

$uuid = $data['uuid'];
$token = $data['token'];
$token = ($token & ~0x3F) | ((floor($time+$time_to_wait) ^ (hexdec(substr($uuid, -2)))) & 0x3F);

$token ^= (1 << $argv[1]);

$url = "http://totp.challenges.bsidestlv.com/validate/$uuid:$token";
echo "Time: $time, ".date(DATE_RFC2822, $time)."\n";
echo "$url\n";
echo file_get_contents($url);
*/
