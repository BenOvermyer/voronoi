<?php

namespace Voronoi;

class Vector extends Point
{
    /**
     * Creates a vector from two points.
     *
     * @param Point $p1
     * @param Point $p2
     * @return Vector
     */
    public static function fromPoints(Point $p1, Point $p2): Vector
    {
        return new self(
            $p2->x - $p1->x,
            $p2->y - $p1->y,
            (($p1->z != null && $p2->z != null) ? $p2->z - $p1->z : null)
        );
    }

    /**
     * Calculate the dot product between the vector and another.
     *
     * @param Vector $vector
     * @return float
     */
    public function scalarProduct(Vector $vector): float
    {
        $scalarZ = ($this->z != null && $vector->z != null) ? $this->z * $vector->z : 0;
        return ($this->x * $vector->x + $this->y * $vector->y + $scalarZ);
    }

    /**
     * Calculate the cross product between the vector and another.
     *
     * @param Vector $vector
     * @return Vector
     */
    public function vectorProduct(Vector $vector): Vector
    {
        return new Vector(
            $this->y * $vector->z - $this->z * $vector->y,
            $this->z * $vector->x - $this->x * $vector->z,
            $this->x * $vector->y - $this->y * $vector->x
        );
    }
}