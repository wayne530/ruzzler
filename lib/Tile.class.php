<?php

class Tile {

    /** attribute flags for tile */
    const ATTR_NONE = 0x0;
    const ATTR_DOUBLE_LETTER = 0x1;
    const ATTR_TRIPLE_LETTER = 0x2;
    const ATTR_DOUBLE_WORD = 0x4;
    const ATTR_TRIPLE_WORD = 0x8;

    /** @var array  map of letter to tile point value */
    private static $letterPoints = array(
        'a' => 1, 'b' => 4, 'c' => 4, 'd' => 2, 'e' => 1, 'f' => 4, 'g' => 1, 'h' => 4,
        'i' => 1, 'j' => 10, 'k' => 1, 'l' => 1, 'm' => 3, 'n' => 1, 'o' => 1, 'p' => 4,
        'q' => 10, 'r' => 1, 's' => 1, 't' => 1, 'u' => 2, 'v' => 4, 'w' => 4, 'x' => 1,
        'y' => 4, 'z' => 1,
    );

    /** @var int  attributes of tile, e.g. double letter, triple word, etc */
    private $attr = 0;

    /** @var string  letter */
    private $letter;

    /** @var array  neighboring tiles keyed by letter, point to Tile[] */
    private $neighbors = array();

    /** @var array  (row, col) 0-based */
    private $coords;

    /** @var int  tile point value */
    private $value;

    /** @var bool  whether the tile is already used in the current word context */
    private $isUsed = false;

    /**
     * construct new tile of specified attributes at the specified coordinates
     *
     * @throws Exception  if invalid letter provided
     *
     * @param string $letter  letter a-z
     * @param int $attr  attribute of tile, if any
     * @param array $coords  (row, col) 0-based position of tile
     */
    public function __construct($letter, $attr, array $coords) {
        if (! preg_match('/^[a-z]$/i', $letter)) {
            throw new Exception('Invalid letter [' . $letter . ']');
        }
        $this->letter = strtolower($letter);
        $this->attr = $attr;
        $this->coords = $coords;
        $this->value = self::$letterPoints[$this->letter];
        if ($this->attr & self::ATTR_DOUBLE_LETTER) {
            $this->value *= 2;
        } else if ($this->attr & self::ATTR_TRIPLE_LETTER) {
            $this->value *= 3;
        }
    }

    /**
     * letter getter
     *
     * @return string
     */
    public function getLetter() {
        return $this->letter;
    }

    /**
     * coords getter
     *
     * @return array
     */
    public function getCoords() {
        return $this->coords;
    }

    /**
     * does tile have a neighbor with the provided letter?
     *
     * @param string $letter
     *
     * @return bool
     */
    public function hasNeighbor($letter) {
        $letter = strtolower($letter);
        return array_key_exists($letter, $this->neighbors);
    }

    /**
     * get all neighbors of this tile with the provided letter, if any
     *
     * @param string $letter
     * @return Tile[]|null  array of tiles; null if no neighbor with provided letter
     */
    public function getNeighbors($letter) {
        $letter = strtolower($letter);
        if (! $this->hasNeighbor($letter)) {
            return NULL;
        }
        return $this->neighbors[$letter];
    }

    /**
     * assign provided tiles as neighbors to this tile
     *
     * @param &Tile[] $tiles
     */
    public function setNeighbors(array &$tiles) {
        $this->neighbors = array();
        foreach ($tiles as &$tile) {
            $this->addNeighbor($tile);
        }
    }

    /**
     * add neighboring tile
     *
     * @param Tile $tile
     */
    public function addNeighbor(Tile &$tile) {
        $letter = $tile->getLetter();
        if (! isset($this->neighbors[$letter])) {
            $this->neighbors[$letter] = array();
        }
        $this->neighbors[$letter][] = $tile;
    }

    /**
     * get tile point value
     *
     * @return int
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * get word multiplier for tile
     *
     * @return int
     */
    public function getWordMultiplier() {
        if ($this->attr & self::ATTR_DOUBLE_WORD) {
            return 2;
        } else if ($this->attr & self::ATTR_TRIPLE_WORD) {
            return 3;
        }
        return 1;
    }

    /**
     * return whether the current tile is used
     *
     * @return bool
     */
    public function isUsed() {
        return $this->isUsed;
    }

    /**
     * set tile's isUsed flag
     *
     * @param bool $isUsed
     */
    public function setIsUsed($isUsed = true) {
        $this->isUsed = $isUsed;
    }

    /**
     * string formatting for class
     *
     * @return string
     */
    public function __toString() {
        return get_class($this) . '[' . $this->letter . ', (' . $this->coords[0] . ', ' . $this->coords[1] . ')]';
    }

}