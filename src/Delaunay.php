<?php

namespace Voronoi;

class Delaunay
{
    /**
     * Triangulate the points
     *
     * @param array $points of Point
     * @return array of Triangle
     */
    public static function triangulate(array $points): array
    {
        // We record the name of points
        $nv = count($points);

        // We calculate the maximal name of triangles
        $triMax = $nv * 4;

        // We look for the outer edges of the points
        $xMin = $points[0]->x;
        $yMin = $points[0]->y;
        $xMax = $xMin;
        $yMax = $yMin;

        for ($i = 0; $i < count($points); $i++) {
            // We add an ID to the point and we get it
            $point = $points[$i]->setId($i);

            // We compare its coordinates to the limits
            if ($point->x < $xMin) {
                $xMin = $point->x;
            } else if ($point->x > $xMax) {
                $xMax = $point->x;
            }

            if ($point->y < $yMin) {
                $yMin = $point->y;
            } else if ($point->y > $yMax) {
                $yMax = $point->y;
            }
        }

        // We calculate the maximum coordinate differences
        $dx = $xMax - $xMin;
        $dy = $yMax - $yMin;

        // We then create the super-triangle, which contains all the points.
        $dmax = ($dx > $dy) ? $dx : $dy;
        $xmid = ($xMax + $xMin) / 2;
        $ymid = ($yMax + $yMin) / 2;

        // We add our three points
        $p1 = new Point(($xmid - 2 * $dmax), ($ymid - $dmax));
        $p2 = new Point($xmid, ($ymid + 2 * $dmax));
        $p3 = new Point(($xmid + 2 * $dmax), ($ymid - $dmax));

        // We add IDs to the points
        $p1->setId($nv + 1);
        $p2->setId($nv + 2);
        $p3->setId($nv + 3);

        // We add these points to the list
        $points[] = $p1;
        $points[] = $p2;
        $points[] = $p3;

        // We create the list of triangles and we add the super-triangle
        $triangles = [];
        $triangles[] = new Triangle($p1, $p2, $p3);

        // Add the points 1 by 1
        for ($i = 0; $i < $nv; $i++) {
            $edges = [];

            // Set up the edge buffer.
            // If the point (Vertex(i).x,Vertex(i).y) lies inside the circumcircle then the
            // three edges of that triangle are added to the edge buffer and the triangle is removed from list.
            for ($j = 0; $j < count($triangles); $j++) {
                // We test if the point is in the circumscribed circle of the triangle
                if ($triangles[$j]->pointInCircle($points[$i])) {
                    $edges[] = new Edge($triangles[$j]->p1, $triangles[$j]->p2);
                    $edges[] = new Edge($triangles[$j]->p2, $triangles[$j]->p3);
                    $edges[] = new Edge($triangles[$j]->p3, $triangles[$j]->p1);

                    // We remove the triangle
                    array_splice($triangles, $j, 1);
                    $j--;
                }
            }

            // If the last point removed was the last in the array
            if ($i >= $nv) {
                continue;
            }

            // We remove duplicate lines
            for ($j = count($edges) - 2; $j >= 0; $j--) {
                for ($k = count($edges) - 1; $k >= $j + 1; $k--) {
                    // If the two lines are equal, we delete them
                    if ($edges[$j]->equals($edges[$k])) {
                        array_splice($edges, $k, 1);
                        array_splice($edges, $j, 1);
                        $k--;
                    }
                }
            }

            // We create new triangles for the current point
            for ($j = 0; $j < count($edges); $j++) {
                if (count($triangles) >= $triMax) {
                    echo "Nombre maximum de edges dépassé";
                }

                // We add the triangle
                $triangles[] = new Nurbs_Triangle($edges[$j]->p1, $edges[$j]->p2, $points[$i]);
            }

            // We purge the list of edges
            $edges = [];
        }

        // We remove the triangles having edges of the super triangle
        for ($i = count($triangles) - 1; $i >= 0; $i--) {
            if ($triangles[$i]->p1->id >= $nv || $triangles[$i]->p2->id >= $nv || $triangles[$i]->p3->id >= $nv) {
                array_splice($triangles, $i, 1);
            }
        }

        // We return the array of triangles
        return $triangles;
    }
}
