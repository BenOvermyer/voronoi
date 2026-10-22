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
        if ($this->x != $point->x || $this->y != $point->y) {
            return false;
        }

        // Elevation presence matters: z = 0 is not the same as "no elevation".
        if ($this->z === null || $point->z === null) {
            return $this->z === null && $point->z === null;
        }

        return $this->z == $point->z;
    }

    /**
     * Whether an elevation coordinate is defined.
     */
    public function hasElevation(): bool
    {
        return $this->z !== null;
    }

    public function __toString()
    {
        return '(' . $this->x . ',' . $this->y . ($this->z !== null ? ',' . $this->z : '') . ')';
    }
}
