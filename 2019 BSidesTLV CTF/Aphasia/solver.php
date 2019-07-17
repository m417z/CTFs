<?php

$sha256_index = [];
for ($i = 0; $i <= 0xFF; $i++) {
    $id = str_pad(dechex($i), 2, '0', STR_PAD_LEFT);
    $hash = hash('sha256', $id);
    $sha256_index[$hash] = $id;
}

$index = get_index_from_file();
$data = get_nonsecure_data_from_file();
foreach ($data as $i => $json_str) {
    if ($json_str != '???') {
        continue;
    }

    $data[$i] = get_nonsecure_data_item($i, json_decode($index[$i], true), $sha256_index);
}

print_r($data);
file_put_contents('data.txt', serialize($data));

////////////////////////////////////////////////////////////////////////

function get_index_from_file() {
    return unserialize(file_get_contents('index.txt'));
}

function get_index_from_server() {
    $index = [];

    for ($i=0; $i<=0x7F; $i++) {
        $id = dechex($i);
        $url = "http://memenc.challenges.bsidestlv.com/$id";
        $contents = file_get_contents($url);
        $index[$i] = $contents;
        echo '.';
    }

    echo "\n";

    //print_r($index);
    //file_put_contents('index.txt', serialize($index));

    return $index;
}

function get_nonsecure_data_from_index($index) {
    $nonsecure_data = [];
    
    foreach ($index as $i => $json_str) {
        $json = json_decode($json_str, true);
        $type = $json['header'];
        $hex_len = $json['length'];
        $len = hexdec($hex_len);
        $id = dechex($i);
        if ($type == 'nonSecure') {
            $url = "http://memenc.challenges.bsidestlv.com/read/$id:$hex_len";
            $contents = file_get_contents($url);
            $nonsecure_data[$i] = $contents;
        } else {
            $nonsecure_data[$i] = '???';
        }
    
        echo '.';
    }
    
    //print_r($nonsecure_data);
    //file_put_contents('nonsecure_data.txt', serialize($nonsecure_data));

    return $nonsecure_data;
}

function get_nonsecure_data_from_file() {
    return unserialize(file_get_contents('nonsecure_data.txt'));
}
/*
function build_hash_index($nonsecure_data) {
    $hash_index = [];

    foreach ($nonsecure_data as $i => $json_str) {
        if ($json_str == '???') {
            continue;
        }

        $json = json_decode($json_str, true);
        $data = $json['data'];
        $id = dechex($i);
        foreach (explode(',', rtrim(chunk_split($data, 2, ','), ',')) as $offset => $byte) {
            if (isset($hash_index[$byte])) {
                continue;
            }

            $hex_offset = dechex($offset);

            $url = "http://memenc.challenges.bsidestlv.com/hash/$id:1:$hex_offset";
            $contents = file_get_contents($url);
            if ($contents == '{"header":"Error","length":"","data":""}'."\n") {
                exit("$url\n");
            }
            $hash_index[$byte] = $contents;
            echo '.';
        }
    }
    echo "\n";

    //print_r($hash_index);
    //file_put_contents('hash_index.txt', serialize($hash_index));

    return $hash_index;
}
*/
function get_nonsecure_data_item($i, $index_data, $sha256_index) {
    $len = hexdec($index_data['length']);
    $decoded = '';

    for ($offset=0; $offset<$len; $offset++) {
        $id = dechex($i);
        $offset_hex = dechex($offset);
        $url = "http://memenc.challenges.bsidestlv.com/hash/$id:1:$offset_hex";
        $contents = file_get_contents($url);
        $contents_decoded = json_decode($contents, true);
        $hash = $contents_decoded['data'];
        $decoded .= $sha256_index[$hash];
        echo '.';
    }

    echo "\n";
    echo hex2str($decoded) . "\n";

    $index_data['data'] = $decoded;
    return json_encode($index_data);
}

// https://stackoverflow.com/a/14486625
function hex2str($hex) {
    $str = '';
    for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
    return $str;
}
