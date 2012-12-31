<?php

class TrieNode {

    /** @var TrieNode[]  trie nodes keyed by letter */
    private $children = array();

    /** @var Tile[]  tiles forming the prefix up to this point */
    private $tiles = array();

    /** @var int  score of tiles */
    private $score = 0;


    /**
     * add a new trie node with the provided letter, tiles, and score
     *
     * @param string $letter
     * @param Tile[] $tiles
     * @param int $score
     *
     * @return TrieNode
     */
    public function &addChild($letter, &$tiles, $score) {
        if (! isset($this->children[$letter])) {
            $this->children[$letter] = new TrieNode();
            $this->children[$letter]->setTilesAndScore($tiles, $score);
        }
        return $this->children[$letter];
    }

    /**
     * set tiles and score for this node
     *
     * @param Tile[] $tiles
     * @param int $score
     */
    public function setTilesAndScore(&$tiles, $score) {
        $this->tiles = $tiles;
        $this->score = $score;
    }

    /**
     * whether this node has a child with the provided letter transition
     *
     * @param string $letter
     *
     * @return bool
     */
    public function hasChild($letter) {
        return isset($this->children[$letter]);
    }

    /**
     * get node pointed to by provided letter
     *
     * @throws Exception  if provided letter does not exist
     *
     * @param string $letter
     *
     * @return TrieNode  node
     */
    public function &getChild($letter) {
        if (! isset($this->children[$letter])) {
            throw new Exception('no such node');
        }
        return $this->children[$letter];
    }

    /**
     * find node for a given word
     *
     * @param string $word
     * @return TrieNode|null  node, if it exists; null otherwise
     */
    public function findWordNode($word) {
        $wordLen = strlen($word);
        $node = &$this;
        for ($i = 0; $i < $wordLen; $i++) {
            $letter = substr($word, $i, 1);
            if ($node->hasChild($letter)) {
                $node = &$node->getChild($letter);
            } else {
                return NULL;
            }
        }
        return $node;
    }

    /**
     * add a word to the trie
     *
     * @param string $word
     * @param Tile[] $tiles
     * @param Board $board
     */
    public function addWord($word, &$tiles, Board &$board) {
        $wordLen = strlen($word);
        $node = &$this;
        for ($i = 0; $i < $wordLen; $i++) {
            $letter = substr($word, $i, 1);
            $subTiles = array_slice($tiles, 0, $i + 1);
            $subScore = $board->scoreWord($subTiles);
            if ($node->hasChild($letter)) {
                $node = $node->getChild($letter);
                if ($subScore > $node->getScore()) {
                    $node->setTilesAndScore($subTiles, $subScore);
                }
            } else {
                $node = $node->addChild($letter, $subTiles, $subScore);
            }
        }
    }

    /**
     * @return Tile[]
     */
    public function &getTiles() {
        return $this->tiles;
    }

    /**
     * @return int
     */
    public function getScore() {
        return $this->score;
    }

}