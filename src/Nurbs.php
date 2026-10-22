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

    /** @var Triangle[]|null */
    protected ?array $delaunay_surfaces = null;

    /**
     * Fingerprint of the points the cached triangulation was built from.
     */
    protected ?string $delaunay_fingerprint = null;

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
    public function getPoint(int $x, int $y, float $radius = 3): Point
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
        $query = new Point($x, $y);

        foreach ($this->getDelaunaySurfaces() as $triangle) {
            if ($triangle->pointIn($query)) {
                return $triangle->getPoint($x, $y);
            }
        }

        throw new NurbsException(
            sprintf('The point (%d, %d) is outside the Delaunay surface', $x, $y)
        );
    }

    /**
     * Returns the Delaunay triangles of the surface, namely nurbs of 3 points.
     *
     * The triangulation is cached and rebuilt only when the points change.
     *
     * @return array of Triangle
     * @throws NurbsException
     */
    public function getDelaunaySurfaces(): array
    {
        // We check that there are enough points
        if (count($this->points) < 3) {
            throw new NurbsException(
                'There are less than 3 points: impossible to use Delaunay'
            );
        }

        $fingerprint = $this->fingerprint();

        if ($this->delaunay_surfaces === null || $this->delaunay_fingerprint !== $fingerprint) {
            // We use the Delaunay library to retrieve the triangles
            $this->delaunay_surfaces = Delaunay::triangulate($this->points);
            $this->delaunay_fingerprint = $fingerprint;
        }

        return $this->delaunay_surfaces;
    }

    /**
     * Invalidates the cached triangulation.
     */
    public function clearCache(): void
    {
        $this->delaunay_surfaces = null;
        $this->delaunay_fingerprint = null;
    }

    /**
     * Appends points and invalidates the triangulation cache.
     */
    public function addPoints(array $points): void
    {
        parent::addPoints($points);

        $this->clearCache();
    }

    /**
     * Points are mutable, so the cache is validated against their current
     * coordinates on every call. A point addition or a coordinate change
     * therefore never returns stale geometry.
     */
    private function fingerprint(): string
    {
        $parts = [count($this->points)];

        foreach ($this->points as $point) {
            $parts[] = $point->x . ',' . $point->y . ',' . ($point->z ?? '');
        }

        return hash('xxh128', implode(';', $parts));
    }
}
