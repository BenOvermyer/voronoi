<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Voronoi\Voronoi;
use Voronoi\Point;

/**
 * GD only accepts integer coordinates, so convert explicitly instead of
 * relying on the implicit (and lossy) float-to-int conversion.
 */
function toInt(float $value): int
{
    return (int) round($value);
}

function make_image(int $n)
{
    // Create the border box object
    $bbox = new stdClass();
    $bbox->xl = 0;
    $bbox->xr = 400;
    $bbox->yt = 0;
    $bbox->yb = 400;

    $xo = 0;
    $dx = $width = 400;
    $yo = 0;
    $dy = $height = 400;
    $numberOfPoints = 20;

    $sites = [];

    // Create the image
    $im = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($im, 255, 255, 255);
    $red = imagecolorallocate($im, 255, 0, 0);
    $black = imagecolorallocate($im, 0, 0, 0);
    imagefill($im, 0, 0, $white);

    // Create random points and draw them
    for ($i = 0; $i < $numberOfPoints; $i++) {
        $point = new Point(mt_rand($xo, $dx), mt_rand($yo, $dy));
        $sites[] = $point;

        imagerectangle(
            $im,
            toInt($point->x) - 2,
            toInt($point->y) - 2,
            toInt($point->x) + 2,
            toInt($point->y) + 2,
            $black
        );
    }

    $voronoi = new Voronoi();
    $diagram = $voronoi->compute($sites, $bbox);

    foreach ($diagram['cells'] as $cell) {
        $points = [];

        if (count($cell->half_edges) > 0) {
            $v = $cell->half_edges[0]->getStartPoint();
            if ($v) {
                $points[] = toInt($v->x);
                $points[] = toInt($v->y);
            }

            for ($i = 0; $i < count($cell->half_edges); $i++) {
                $halfedge = $cell->half_edges[$i];
                $edge = $halfedge->edge;

                if ($edge->va && $edge->vb) {
                    imageline(
                        $im,
                        toInt($edge->va->x),
                        toInt($edge->va->y),
                        toInt($edge->vb->x),
                        toInt($edge->vb->y),
                        $red
                    );
                }

                $v = $halfedge->getEndPoint();
                if ($v) {
                    $points[] = toInt($v->x);
                    $points[] = toInt($v->y);
                }
            }
        }

        // GD needs at least three vertices for a polygon.
        if (count($points) < 6) {
            continue;
        }

        // Draw Thyssen polygon. PHP 8.1+ expects the three-argument form:
        // imagefilledpolygon($image, array $points, int $color).
        $color = imagecolorallocatealpha($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), 50);
        imagefilledpolygon($im, $points, $color);
    }

    // Display image
    imagepng($im, "voronoi-$n.png");
    imagedestroy($im);
}

$n = !empty($argv[1]) ? (int) $argv[1] : 1;

for ($i = 0; $i < $n; $i++) {
    make_image($i);
}
