<?php

namespace Voronoi;

use Voronoi\Exception\NurbsException;

/**
 * This class defines a nurbs, namely a surface in 3 dimensions.
 *
 */
class Nurbs extends AbstractSurface
{
    const CALCULATE_CLASSIC = 1;
    const CALCULATE_DELAUNAY = 2;

    protected int $calculation_method = self::CALCULATE_CLASSIC;

    protected $delaunay_surfaces;

    /**
     * Create a nurbs from an array of points.
     *
     * @param array $points
     * @return AbstractSurface
     */
    public static function fromPoints(array $points)
    {
        $nurbs = new self();

        $nurbs->addPoints($points);

        return $nurbs;
    }

    /**
     * Changes the method of calculating nurbs.
     *
     */
    public function setCalculationMethod($method)
    {
        $this->calculation_method = $method;
    }

    /**
     * Collect a point from the nurbs.
     *
     * @param integer $x
     * @param integer $y
     * @param float $radius
     * @return Point
     * @throws NurbsException
     */
    public function getPoint(int $x, int $y, float $radius = 1): Point
    {
        switch ($this->calculation_method) {
            case self::CALCULATE_DELAUNAY:
                return $this->getDelaunayPoint($x, $y);
            default:
                return parent::getPoint($x, $y, $radius);
        }
    }

    /**
     * Calculate a point using the Delaunay method, namely: we divide the surface into other triangular surfaces that are easier to calculate.
     *
     * @param integer $x
     * @param integer $y
     * @return Point
     * @throws NurbsException
     */
    public function getDelaunayPoint(int $x, int $y): Point
    {
        $triangles = $this->getDelaunaySurfaces();

        var_dump($triangles);
    }

    /**
     * Returns the Delaunay triangles of the surface, namely nurbs of 3 points.
     *
     * @return array of Nurbs
     * @throws NurbsException
     */
    public function getDelaunaySurfaces()
    {
        // We check that there are enough points
        if (count($this->points) < 3) {
            throw new NurbsException(
                'There are less than 3 points: impossible to use Delaunay'
            );
        }

        // We use the Delaunay library to retrieve the triangles
        return Delaunay::triangulate($this->points);
    }
}
