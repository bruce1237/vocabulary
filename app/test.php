<?php
// Define the list of words
$words = [
    "Abate",
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
$numberOfWordsToSelect = 3;

// Shuffle the array to randomize the order
shuffle($words);

// Select the first $numberOfWordsToSelect words from the shuffled array
$selectedWords = array_slice($words, 0, $numberOfWordsToSelect);

$selected = implode(PHP_EOL, $selectedWords);
file_put_contents("select", $selected);

// Get the remaining words
$remainingWords = array_slice($words, $numberOfWordsToSelect);
$remain = implode(PHP_EOL, $remainingWords);
file_put_contents("REM", $remain);

