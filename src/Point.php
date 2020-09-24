<?php

namespace Voronoi;

/**
 * Represents a point defined by its three coordinates.
 *
 */
class Point
{
    public $x;
    public $y;
    public $z;

    public $id;

    public $halfedges = [];

    /**
     * Constructeur.
     *
     */
    public function __construct($x, $y, $z = null)
    {
        $this->x = $x;
        $this->y = $y;
        $this->z = $z;
    }

    /**
     * Associates an ID with the point.
     *
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * See if two points are the same.
     * @param Point $point
     * @return bool
     */
    public function equals(Point $point): bool
    {
        return ($this->x == $point->x && $this->y == $point->y && (($this->z == null && $point->z == null) || $this->z == $point->z));
    }

    public function __toString()
    {
        return '(' . $this->x . ',' . $this->y . ($this->z != null ? ',' . $this->z : '') . ')';
    }
}