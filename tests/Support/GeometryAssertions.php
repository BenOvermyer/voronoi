<?php

namespace Tests\Support;

use Voronoi\Cell;

trait GeometryAssertions
{
    protected function boundingBox(float $width = 400, float $height = 400): \stdClass
    {
        $bbox = new \stdClass();
        $bbox->xl = 0.0;
        $bbox->xr = $width;
        $bbox->yt = 0.0;
        $bbox->yb = $height;

        return $bbox;
    }

    /**
     * Shoelace area of a cell, built from the start points of its half edges.
     */
    protected function cellArea(Cell $cell): float
    {
        $points = [];

        foreach ($cell->half_edges as $halfEdge) {
            $vertex = $halfEdge->getStartPoint();
            if ($vertex) {
                $points[] = [$vertex->x, $vertex->y];
            }
        }

        $count = count($points);
        if ($count < 3) {
            return 0.0;
        }

        $area = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $j = ($i + 1) % $count;
            $area += $points[$i][0] * $points[$j][1] - $points[$j][0] * $points[$i][1];
        }

        return abs($area) / 2;
    }

    /**
     * Whether the end point of every half edge matches the start point of the
     * next one, i.e. the polygon is closed.
     */
    protected function isCellClosed(Cell $cell): bool
    {
        $count = count($cell->half_edges);
        if ($count === 0) {
            return false;
        }

        for ($i = 0; $i < $count; $i++) {
            $end = $cell->half_edges[$i]->getEndPoint();
            $start = $cell->half_edges[($i + 1) % $count]->getStartPoint();

            if (!$end || !$start) {
                return false;
            }

            if (abs($end->x - $start->x) > 1e-6 || abs($end->y - $start->y) > 1e-6) {
                return false;
            }
        }

        return true;
    }
}
