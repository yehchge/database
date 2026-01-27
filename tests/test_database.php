<?php

require '../inc/config.php';
require '../inc/Database.php';

define('_TEST_HOST', 'localhost');
define('_TEST_PORT', '3306');
define('_TEST_DB',   'test');
define('_TEST_USER', 'robot');
define('_TEST_PASS', '');


$oDB = Database::oDB('TEST');
$oDB->bSetCharacter('utf8mb4');
$test = new Database(_TEST_DB, _TEST_HOST, _TEST_USER, _TEST_PASS);
$test->bSetCharacter('utf8mb4');

$topic_no = $test->bInsert("testtitle", [
    'title' => "test".date('Y-m-d H:i:s')
]);

echo "Add topic_no: $topic_no\n";

$test->bUpdate("testtitle", [
    'topic_no' => $topic_no
], [
    'title' => "GoodBoys".date('Y-m-d H:i:s')
]);

echo "Update topic_no: $topic_no\n";

$query = $oDB->iQuery("SELECT * FROM testtitle WHERE title LIKE '%".date('Y-m-d')."%'");

$iTotal = $oDB->iNumRows($query);
echo "Total: $iTotal rows\n";

while($row = $oDB->aFetchAssoc($query)){
    echo "title = {$row['title']}\n";
}

echo "List title Like: ".date('Y-m-d')."\n";
