# PHP implementation of Steven Fortune's Voronoï algorithm.

This lets you create Voronoï graphs automatically by computing the polygon coordinates based on a set of points.

It was originally written by Samuel Roze based on a JavaScript library by Raymond Hill.

## Example Usage

To generate polygons, you need to have a _bounding box_ that will define the box within you'll compute your graph.
Then, you need some points. Here's a simple snippet that generate random points, and them compute the polygons.

For a more complete example, check out the file `sample/voronoi.php`.

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
```

You now have the cells (polygons) in the `$diagram['cells']` variable. With this, you can draw an image that represents 
the points and the polygons:

```php
// Create image using GD
$im = imagecreatetruecolor(400, 400);

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
 
	if (count($cell->half_edges) > 0) {
		$v = $cell->half_edges[0]->getStartPoint();
		if ($v) {
			$points[] = $v->x;
			$points[] = $v->y;
		} else {
			var_dump($j.': no start point');
		}
 
		for ($i = 0; $i < count($cell->half_edges); $i++) {
			$halfedge = $cell->half_edges[$i];
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
