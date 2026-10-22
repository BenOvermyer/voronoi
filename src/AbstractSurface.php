<?php

namespace Voronoi;

use Voronoi\Exception\NurbsException;

abstract class AbstractSurface
{
    /** @var Point[] */
    protected array $points = [];

    /**
     * Append points to the surface.
     *
     * Values are always appended, so several batches using the same
     * zero-based keys are all kept.
     *
     * @param Point[] $points
     */
    public function addPoints(array $points): void
    {
        foreach ($points as $point) {
            $this->points[] = $point;
        }
    }

    /**
     * Returns a point
     *
     * @param integer $x
     * @param integer $y
     * @param float $radius We only keep the X most appropriate
     * @return Point
     * @throws NurbsException
     */
    public function getPoint(int $x, int $y, float $radius = 3): Point
    {
        if (count($this->points) === 0) {
            throw new NurbsException('There are no points: impossible to interpolate');
        }

        // We only keep the X closest points, where X = $radius.
        $neighbours = $this->getClosestPoints($x, $y, max(1, (int) $radius));

        // We will calculate the weights
        $weights = [];
        $weightsSum = 0;
        $distanceMin = $neighbours[0]['distance'];
        $distanceMax = $neighbours[count($neighbours) - 1]['distance'];
        foreach ($neighbours as $neighbour) {
            // We calculate the weight
            $weight = $this->getWeight($neighbour['distance'], $distanceMin, $distanceMax);
            $weightsSum += $weight;

            // Add the point and its weight to the table
            $weights[] = [
                'point' => $neighbour['point'],
                'weight' => $weight,
            ];
        }

        // We will now use the properties of the barycenters to calculate the height of the point
        $sum = 0;
        foreach ($weights as $weight) {
            $sum += $weight['weight'] * ($weight['point']->z ?? 0);
        }

        // We calculate the height
        $z = $weightsSum == 0 ? 0 : $sum / $weightsSum;

        // We return the calculated point
        return new Point($x, $y, $z);
    }

    /**
     * Returns the closest points to the given coordinates, ordered by
     * Euclidean distance and limited to $limit entries.
     *
     * @return array<int, array{distance: float, point: Point}>
     */
    private function getClosestPoints(int $x, int $y, int $limit): array
    {
        // Parallel arrays keep the hot loop cheap: squared distances are
        // compared without a sqrt(), and only the retained samples get one.
        $squaredDistances = [];
        $closestPoints = [];
        $count = 0;
        $worstIndex = 0;
        $worstSquaredDistance = -INF;

        foreach ($this->points as $point) {
            $dx = $point->x - $x;
            $dy = $point->y - $y;

            // An exact match is returned as-is, regardless of the radius.
            if ($dx == 0 && $dy == 0) {
                return [['distance' => 0.0, 'point' => $point]];
            }

            // Euclidean distance, compared squared: the previous
            // implementation ranked the Manhattan distance because it summed
            // the absolute deltas.
            $squaredDistance = $dx * $dx + $dy * $dy;

            // Bounded selection: keep the $limit closest samples while
            // scanning, instead of sorting every distance for every query.
            if ($count < $limit) {
                $squaredDistances[] = $squaredDistance;
                $closestPoints[] = $point;
                $count++;

                if ($squaredDistance > $worstSquaredDistance) {
                    $worstSquaredDistance = $squaredDistance;
                    $worstIndex = $count - 1;
                }

                continue;
            }

            if ($squaredDistance < $worstSquaredDistance) {
                $squaredDistances[$worstIndex] = $squaredDistance;
                $closestPoints[$worstIndex] = $point;

                // The worst sample was replaced, so find the new worst one.
                $worstSquaredDistance = -1.0;
                foreach ($squaredDistances as $index => $value) {
                    if ($value > $worstSquaredDistance) {
                        $worstSquaredDistance = $value;
                        $worstIndex = $index;
                    }
                }
            }
        }

        // Sort the retained samples by distance; PHP's sorts are stable, so
        // equidistant samples keep their input order.
        asort($squaredDistances);

        $neighbours = [];
        foreach (array_keys($squaredDistances) as $index) {
            $neighbours[] = [
                'distance' => sqrt($squaredDistances[$index]),
                'point' => $closestPoints[$index],
            ];
        }

        return $neighbours;
    }

    /**
     * Transforms a distance into a weight. Allows to equalize as desired.
     * @param float $distance
     * @param float $min
     * @param float $max
     * @return float
     */
    public function getWeight(float $distance, float $min, float $max): float
    {
        // With a single neighbour (or several equidistant ones) the distance
        // range is empty, so every sample gets the same weight.
        if ($min <= 0 || $min == $max) {
            return 1.0;
        }

        return ($max / $min) * $distance + $max;
    }

    /**
     * Returns the list of points.
     *
     */
    public function getPoints(): array
    {
        return $this->points;
    }
}
