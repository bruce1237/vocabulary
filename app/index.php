<?php
include "./src/dictory.php";


$dic = new dictory();
// $dic->init();
// $dic->reset();
// $dic->manualRest();

// $rs = $dic->getWord($_GET['word']);

// $rs = $dic->getWord('insurmountable');
// $rs = $dic->getProgress();
// echo $rs;
// $rs = $dic->getWordListFromWorkingInProgress();
// die("AAA");

// var_dump($rs);


if (isset($_POST['type'])) {

    switch ($_POST['type']) {
        case "progress":
            $rs = $dic->getProgress();
            break;
        case "getWordList":
            $rs = $dic->getWordListFromWorkingInProgress();
            break;
        case "getWord":
            $rs = $dic->getWord($_POST['word']);
            // echo $rs;
            break;
        case "getWordTest":
            $rs = $dic->getWordTest($_POST['word']);
            // echo $rs;
            break;
        case "checkAnswer":
            $rs = $dic->checkAnswer($_POST['word'], $_POST['answer']);
            break;
        default:
    }

    echo $rs;
}
