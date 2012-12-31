<?php

require_once(dirname(__FILE__).'/Tile.class.php');
require_once(dirname(__FILE__).'/TrieNode.class.php');

class Board {

    /** board dimensions */
    const NUM_ROWS = 4;
    const NUM_COLS = 4;

    /** k-value if using k-hash filtering */
    const K_SIZE = 4;

    /** @var bool  whether to use k-hash filtering */
    protected $useKHashFiltering = true;

    /** @var bool  whether to use trie-based optimization (may yield sub-optimal scores for words) */
    protected $useTrieOptimization = false;

    /** @var array  2-d grid of tiles */
    private $tiles = array();

    /** @var array  keyed by letter -> Tile[] */
    private $tilesByLetter = array();

    /** @var array  map of all k-hashes as keys */
    private $kHashes = array();

    /** @var TrieNode  root of trie */
    private $trie;

    /** @var bool  whether the board data structure is fully built */
    private $isBuilt = false;

    /**
     * add new tile across the board
     *
     * @param string $letter  letter
     * @param int $attributes  attributes the tile will have
     */
    public function addTile($letter, $attributes = Tile::ATTR_NONE) {
        static $row = 0;
        static $col = 0;
        $tile = new Tile($letter, $attributes, array($row, $col));
        if (! isset($this->tiles[$row])) {
            $this->tiles[$row] = array();
        }
        $this->tiles[$row][] = &$tile;
        if (! isset($this->tilesByLetter[$tile->getLetter()])) {
            $this->tilesByLetter[$tile->getLetter()] = array();
        }
        $this->tilesByLetter[$tile->getLetter()][] = &$tile;
        $col++;
        if ($col >= self::NUM_COLS) {
            $row++;
            $col = 0;
        }
        if ($row >= self::NUM_ROWS) {
            $this->buildIfNeeded();
        }
    }

    /**
     * get tile at specified coordinates
     *
     * @param int $row
     * @param int $col
     *
     * @return Tile
     */
    public function &getTile($row, $col) {
        return $this->tiles[$row][$col];
    }

    /**
     * get neighboring cells of the cell with provided coordinates
     *
     * @param int $row
     * @param int $col
     *
     * @return Tile[]
     */
    private function getNeighborsOf($row, $col) {
        /** @var Tile[] $tiles */
        $tiles = array();
        for ($r = $row - 1; $r <= $row + 1; $r++) {
            for ($c = $col - 1; $c <= $col + 1; $c++) {
                // skip illegal coords and the reference coord
                if (($r < 0) || ($r >= self::NUM_ROWS) || ($c < 0) || ($c >= self::NUM_COLS) || (($r == $row) && ($c == $col))) { continue; }
                $tiles[] = $this->getTile($r, $c);
            }
        }
        return $tiles;
    }

    /**
     * build intermediate data structures - runs once
     */
    private function buildIfNeeded() {
        if ($this->isBuilt) { return; }
        $kHashes = array();
        for ($row = 0; $row < self::NUM_ROWS; $row++) {
            for ($col = 0; $col < self::NUM_COLS; $col++) {
                $this->getTile($row, $col)->setNeighbors($this->getNeighborsOf($row, $col));
            }
        }
        if ($this->useKHashFiltering) {
            for ($row = 0; $row < self::NUM_ROWS; $row++) {
                for ($col = 0; $col < self::NUM_COLS; $col++) {
                    foreach ($this->getTile($row, $col)->getKPrefixes(self::K_SIZE) as $prefix) {
                        $kHashes[$prefix] = true;
                    }
                }
            }
        }
        $this->kHashes = &$kHashes;
        if ($this->useTrieOptimization) {
            $this->trie = new TrieNode();
        }
        $this->isBuilt = true;
    }

    /**
     * find word in board
     *
     * @param string $word
     *
     * @return array|null  array of key-value pairs with keys 'startCoord', 'tiles', 'score' corresponding to highest score match; null if no match
     */
    public function findWord($word) {
        $this->buildIfNeeded();
        $word = strtolower(trim($word));
        $wordLen = strlen($word);
        if (($wordLen > self::NUM_ROWS * self::NUM_COLS) || ($wordLen < 2)) {
            // impossible to spell this word on this board or does not meet min word length
            return NULL;
        }
        $firstLetter = substr($word, 0, 1);
        if (! isset($this->tilesByLetter[$firstLetter])) {
            // skip word - first letter does not exist on this board
            return NULL;
        }
        if ($this->useKHashFiltering && ($wordLen >= self::K_SIZE)) {
            // reject word if k-prefix of word does not exist on this board
            $kPrefix = substr($word, 0, self::K_SIZE);
            if (! isset($this->kHashes[$kPrefix])) {
                return NULL;
            }
        }
        if ($this->useTrieOptimization && ! is_null($node = $this->trie->findWordNode($word))) {
            /**
             * assume that the substring of a previously located word is itself optimal along the tile path of the
             * previously located word. note this is a heuristic and does not necessarily yield the most optimal
             * placement of substrings.
             */
            $tiles = &$node->getTiles();
            return array(
                'word' => $word,
                'startCoord' => $tiles[0]->getCoords(),
                'tiles' => &$tiles,
                'score' => $node->getScore(),
            );
        } else {
            $results = $this->findWordProper($this->tilesByLetter[$firstLetter], substr($word, 1));
            if (count($results) < 1) { return NULL; }
            $matches = array();
            foreach ($results as &$tiles) {
                /** @var Tile[] $tiles */
                $matches[] = array(
                    'word' => $word,
                    'startCoord' => $tiles[0]->getCoords(),
                    'tiles' => &$tiles,
                    'score' => $this->scoreWord($tiles),
                );
            }
            usort($matches, function($a, $b) {
                if ($a['score'] == $b['score']) { return 0; }
                return $a['score'] < $b['score'] ? 1 : -1;
            });
            if ($this->useTrieOptimization) {
                $this->trie->addWord($matches[0]['word'], $matches[0]['tiles'], $this);
            }
            return $matches[0];
        }
    }

    /**
     * score an array of tiles forming a word
     *
     * @param Tile[] $tiles
     *
     * @return int
     */
    public function scoreWord(array &$tiles) {
        $wordMultiplier = 1;
        $wordScore = 0;
        foreach ($tiles as &$tile) {
            $wordScore += $tile->getValue();
            $wordMultiplier *= $tile->getWordMultiplier();
        }
        return $wordScore * $wordMultiplier;
    }

    /**
     * search graph for matches
     *
     * @param Tile[] $tiles
     * @param string $word
     *
     * @return array
     */
    private function findWordProper(array &$tiles, $word) {
        $matches = array();
        if (strlen($word) < 1) {
            return $tiles;
        }
        $firstLetter = substr($word, 0, 1);
        foreach ($tiles as &$tile) {
            $tile->setIsUsed(true);
            if ($tile->hasNeighbor($firstLetter)) {
                $wordSuffixes = $this->findWordProper($tile->getNeighbors($firstLetter, true), substr($word, 1));
                foreach ($wordSuffixes as $wordSuffix) {
                    if (is_array($wordSuffix)) {
                        $matches[] = array_merge(array(&$tile), $wordSuffix);
                    } else {
                        $matches[] = array(&$tile, $wordSuffix);
                    }
                }
            }
            $tile->setIsUsed(false);
        }
        return $matches;
    }

}
