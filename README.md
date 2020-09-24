# PHP implementation of Steven Fortune's Voronoï algorithm.

This lets you create Voronoï graphs (also called Thyssen polygons) automatically by computing the polygon coordinates based on the points.

It was originally written by Samuel Roze based on a JavaScript library by Raymond Hill.

## Example usage

To generate polygons, you need to have a _bounding box_ that will define the box within you'll compute your graph.
Then, you need some points. Here's a simple snippet that generate random points, and them compute the polygons.

Note that bounding box is in the var `$bbox`, and points in `$sites`.

```php
<?php 

use Voronoi\Voronoi;
use Voronoi\Point;
 
// Create the bounding box
$bbox = new stdClass();
$bbox->xl = 0;
$bbox->xr = 400;
$bbox->yt = 0;
$bbox->yb = 400;

// Define generated points bounds
$xo = 0;
$dx = $width = 400;
$yo = 0;
$dy = $height = 400;
$n = 20;

// Generate random points
$sites = [];
for ($i=0; $i < $n; $i++) {
    $point = new Point(rand($xo, $dx), rand($yo, $dy));
	  $sites[] = $point;
}

// Compute the diagram
$voronoi = new Voronoi();
$diagram = $voronoi->compute($sites, $bbox);

// You now have the cells (polygons) on the
// $diagram['cells'] variable.
```

You can now draw an image that represent the points and the polygons:
```php
// Create image using GD
$im = imagecreatetruecolor($width, $height);

// Create colors
$white = imagecolorallocate($im, 255, 255, 255);
$red = imagecolorallocate($im, 255, 0, 0);
$green = imagecolorallocate($im, 0, 100, 0);
$black = imagecolorallocate($im, 0, 0, 0);

// Fill white background
imagefill($im, 0, 0, $white);
 
// Draw points
for ($i=0; $i < $n; $i++) {
    $point = $sites[$i];
	  imagerectangle($im, $point->x - 2, $point->y - 2, $point->x + 2, $point->y + 2, $black);
}

// Draw polygons
$j = 0;
foreach ($diagram['cells'] as $cell) {
	$points = array();
 
	if (count($cell->_halfedges) > 0) {
		$v = $cell->_halfedges[0]->getStartPoint();
		if ($v) {
			$points[] = $v->x;
			$points[] = $v->y;
		} else {
			var_dump($j.': no start point');
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
			}
		}
	}
 
	// Create polygon with a random color
	$color = imagecolorallocatealpha($im, rand(0, 255), rand(0, 255), rand(0, 255), 50);
	imagefilledpolygon($im, $points, count($points) / 2, $color);
	$j++;
}
 
// Display image
imagepng($im, 'voronoi.png');

```
