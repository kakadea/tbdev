<?php
ob_start();
require_once __DIR__ . '/../include/benc.php';

$failures = 0;
function check_bencode($condition, $message)
{
    global $failures;
    if ($condition)
        echo "PASS: $message\n";
    else
    {
        echo "FAIL: $message\n";
        $failures++;
    }
}

$fixture = array(
    'type' => 'dictionary',
    'value' => array(
        'announce' => array('type' => 'string', 'value' => 'http://tracker.test/announce.php'),
        'info' => array(
            'type' => 'dictionary',
            'value' => array(
                'length' => array('type' => 'integer', 'value' => '1234'),
                'name' => array('type' => 'string', 'value' => 'legal-fixture.bin'),
                'piece length' => array('type' => 'integer', 'value' => '16384'),
                'pieces' => array('type' => 'string', 'value' => str_repeat('x', 20))
            )
        )
    )
);

$encoded = benc($fixture);
check_bencode($encoded === 'd8:announce32:http://tracker.test/announce.php4:infod6:lengthi1234e4:name17:legal-fixture.bin12:piece lengthi16384e6:pieces20:xxxxxxxxxxxxxxxxxxxxee', 'torrent fixture encodes with sorted dictionary keys');

$decoded = bdec($encoded);
check_bencode(is_array($decoded) && $decoded['type'] === 'dictionary', 'encoded fixture decodes as dictionary');
check_bencode($decoded['value']['info']['value']['name']['value'] === 'legal-fixture.bin', 'decoded name is preserved');
check_bencode($decoded['value']['info']['value']['length']['value'] === '1234', 'decoded integer value is preserved');
check_bencode(benc(array('type' => 'list', 'value' => array(
    array('type' => 'string', 'value' => 'one'),
    array('type' => 'integer', 'value' => '2')
))) === 'l3:onei2ee', 'list fixture encodes correctly');
check_bencode(bdec('d4:infod4:name4:testee') !== false, 'minimal valid dictionary decodes');
check_bencode(bdec('d4:infod4:name4:test') === null, 'truncated dictionary is rejected');
check_bencode(bdec('i03e') === null, 'non-canonical integer is rejected');

if ($failures > 0)
{
    echo "\n$failures test(s) failed.\n";
    exit(1);
}

echo "\nAll bencode tests passed.\n";
ob_end_flush();
?>
