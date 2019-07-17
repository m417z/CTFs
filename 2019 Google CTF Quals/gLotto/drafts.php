<?php

// Attempts and stuff...

/*
    'case day(date) ' .
    'when 1  then  (CONV(@lotto,36,10)&1) ' .
    'when 5  then ((CONV(@lotto,36,10)>>1)&1) ' .
    'when 10 then ((CONV(@lotto,36,10)>>2)&1) ' .
    'when 13 then ((CONV(@lotto,36,10)>>3)&1) ' .
    'when 18 then ((CONV(@lotto,36,10)>>4)&1) ' .
    'when 23 then ((CONV(@lotto,36,10)>>5)&1) ' .
    'when 28 then ((CONV(@lotto,36,10)>>6)&1) ' .
    'when 30 then ((CONV(@lotto,36,10)>>7)&1) ' .
    'end',
*/
/*
function generate_order_by_for_dates($dates) {
    $q = 'case day(date) ';

    $var_letter = 'a';
    $var_letter_results = [];
    $div = 1;
    $mod = count($dates);
    $ifs = '';
    foreach ($dates as $date) {
        if ($div != 1) {
            $val = "@$var_letter := (CAST(CONV(@lotto,36,10) AS SIGNED) div $div)%$mod";
            $var_letter_new_result = $var_letter;
            $var_letter++;

            foreach ($var_letter_results as $var_letter_compare) {
                $val .= "+IF(@$var_letter>=@$prev_var_letter,1,0)";
            }

            $var_letter_results[] = $var_letter_new_result;
        } else {
            $val = "@$var_letter := CAST(CONV(@lotto,36,10) AS SIGNED)%$mod";
            $var_letter_results[] = $var_letter;
            $var_letter++;
        }

        $q .= "when $date then $val $ifs ";
        $div *= $mod;
        $mod--;
    }

    $q .= 'end';

    return $q;
}
*/
/*
function generate_order_by_for_dates($dates) {
    $q = 'case day(date) ';

    $var_letter = 'a';
    $var_letter_results = [];
    $div = 1;
    $mod = 1;
    $dist = 1024;
    foreach ($dates as $date) {
        if ($mod != 1) {
            $val = "(CAST(CONV(@lotto,36,10) AS SIGNED) div $div)%$mod";

            $q .= "when $date then case $val ";

            $var_letter_new_result = $var_letter;
            $i = 0;

            foreach ($var_letter_results as $var_letter_relative) {
                $q .= "when $i then @$var_letter:=@$var_letter_relative-$dist ";
                $i++;
            }
            $q .= "when $i then @$var_letter:=@{$var_letter_results[$i-1]}+$dist ";

            $var_letter_results[] = $var_letter;
            $var_letter++;

            $q .= 'end ';
        } else {
            $val = "@$var_letter := 0";
            $var_letter_results[] = $var_letter;
            $var_letter++;

            $q .= "when $date then $val ";
        }

        $div *= $mod;
        $mod++;
        $dist /= 2;
    }

    $q .= 'end';

    return $q;
}
*/
