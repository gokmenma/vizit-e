<?php
$test = '<soapenv:Body><ns1:wsLoginReturn>test</ns1:wsLoginReturn></soapenv:Body>';
echo "Girdi: " . $test . "\n";

// Mevcut dosyadaki regex
$result1 = preg_replace("/(\<\/?)(\\w+)\\:([^\>]*\>)/", "$1$2$3", $test);
echo "Regex 1 (mevcut): " . $result1 . "\n";

// Temiz regex  
$result2 = preg_replace('/(<\/?)(\w+):([^>]*>)/', '$1$2$3', $test);
echo "Regex 2 (temiz):   " . $result2 . "\n";
