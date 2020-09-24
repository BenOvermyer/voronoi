<?php

namespace Voronoi;

abstract class AbstractSurface
{
    protected array $points = [];

    public function addPoints(array $points)
    {
        $this->points = $this->points + $points;
    }

    /**
     * Returns a point
     *
     * @param integer $x
     * @param integer $y
     * @param float $radius We only keep the X most appropriate
     * @return Point
     */
    public function getPoint(int $x, int $y, float $radius = 3): Point
    {
        // We will calculate the distance on the plane between the requested point and the X others closer and known.
        $distances = [];
        foreach ($this->points as $point) {
            $distance = (int)(sqrt(abs($point->x - $x) + abs($point->y - $y)) * 100);

            // If the distance is equal to 0, it is the same point, so we return the point.
            if ($distance == 0) {
                return $point;
            }

            // We add the point
            if (array_key_exists($distance, $distances)) {
                $distance += 1;
            }
            $distances[$distance] = $point;
        }

        // We will select only the X most appropriate points.
        ksort($distances);

        $points = [];
        $distance_min = null;
        $distance_max = null;
        $i = 0;
        foreach ($distances as $distance => $point) {
            // We add the points
            $points[] = [
                'distance' => $distance,
                'point' => $point,
            ];

            // We calculate the greater or lesser distances
            if ($distance_min === NULL or $distance_min > $distance) {
                $distance_min = $distance;
            }
            if ($distance_max === NULL or $distance_max < $distance) {
                $distance_max = $distance;
            }

            // We only take the first X, where X = $ radius
            if (++$i >= $radius) {
                break;
            }
        }

        // We will calculate the weights
        $weights = [];
        $weightsSum = 0;
        foreach ($points as $distance) {
            // We calculate the weight
            $weight = $this->getWeight($distance['distance'], $distance_min, $distance_max);
            $weightsSum += $weight;

            // Add the point and its weight to the table
            $weights[] = [
                'point' => $distance['point'],
                'weight' => $weight,
            ];
        }

        // We will now use the properties of the barycenters to calculate the height of the point
        $sum = 0;
        foreach ($weights as $weight) {
            $sum += $weight['weight'] * $weight['point']->z;
        }

        // We calculate the height
        $z = $sum / $weightsSum;

        // We return the calculated point
        return new Point($x, $y, $z);
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