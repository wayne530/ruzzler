<?php

ini_set('display_errors', "1");
ini_set('display_startup_errors', true);
error_reporting(E_ALL);

require_once('lib/Tile.class.php');
require_once('lib/Board.class.php');

/**
 * l p e f
 * a o a r
 * w e e s
 * d l h d
 */
$board = new Board();
$tileString = $argv[1];
for ($i = 0; $i < Board::NUM_ROWS * Board::NUM_COLS; $i++) {
    $letter = substr($tileString, $i, 1);
    $board->addTile($letter);
}
$fp = fopen('TWL06_reversed.txt', 'r');
$matches = array();
while ($word = fgets($fp)) {
    $word = strtolower(trim($word));
    $match = $board->findWord($word);
    if (! is_null($match)) {
        $matches[] = $match;
    }
}
fclose($fp);
usort($matches, function($a, $b) {
    if ($a['score'] == $b['score']) { return 0; }
    return $a['score'] < $b['score'] ? 1 : -1;
});
foreach ($matches as &$match) {
    print($match['word'] . ' score:' . $match['score'] . ' tiles:');
    $tileCoords = array();
    /** @var Tile $tile */
    foreach ($match['tiles'] as &$tile) {
        $coords = $tile->getCoords();
        $tileCoords[] = '(' . $coords[0] . ', ' . $coords[1] . ')';
    }
    print(implode(', ', $tileCoords) . "\n");
}
