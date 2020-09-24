<?php

require_once '../vendor/autoload.php';

use Voronoi\Voronoi;
use Voronoi\Point;

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
$green = imagecolorallocate($im, 0, 100, 0);
$black = imagecolorallocate($im, 0, 0, 0);
imagefill($im, 0, 0, $white);

// Create random points and draw them
for ($i = 0; $i < $numberOfPoints; $i++) {
    $point = new Point(mt_rand($xo, $dx), mt_rand($yo, $dy));
    $sites[] = $point;

    imagerectangle($im, $point->x - 2, $point->y - 2, $point->x + 2, $point->y + 2, $black);
}

$voronoi = new Voronoi();
$diagram = $voronoi->compute($sites, $bbox);

$j = 0;

foreach ($diagram['cells'] as $cell) {
    $points = [];

    if (count($cell->_halfedges) > 0) {
        $v = $cell->_halfedges[0]->getStartPoint();
        if ($v) {
            $points[] = $v->x;
            $points[] = $v->y;
        } else {
            var_dump($j . ': no start point');
        }

        for ($i = 0; $i < count($cell->_halfedges); $i++) {
            $halfedge = $cell->_halfedges[$i];
            $edge = $halfedge->edge;

            if ($edge->va && $edge->vb) {
                imageline($im, $edge->va->x, $edge->va->y, $edge->vb->x, $edge->vb->y, $red);
            }

            $v = $halfedge->getEndPoint();
            if ($v) {
                $points[] = $v->x;
                $points[] = $v->y;
            } else {
                var_dump($j . ': no end point #' . $i);
            }
        }
    }

    // Draw Thyssen polygon
    $color = imagecolorallocatealpha($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255), 50);
    imagefilledpolygon($im, $points, count($points) / 2, $color);
    $j++;
}

// Display image
imagepng($im, 'voronoi.png');
imagedestroy($im);
