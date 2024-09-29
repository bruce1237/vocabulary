<?php
// Define the list of words
$words = [
    "onomatopoeia",
    "Aberration",
    "Ablest",
    "Abode",
    "Abrasive",
    "Absolution",
    "Abundance",
    "Accusatory",
    "Acquainted",
    "Adherent"
];

// Number of words to select
include "./src/dictory.php";


$dic = new dictory();
$word="onomatopoeia";
$r = $dic->getThesaurus($word);

var_dump($r);
