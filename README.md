# PHP implementation of Steven Fortune's Voronoï algorithm.

This lets you create Voronoï graphs automatically by computing the polygon coordinates based on a set of points.

It was originally written by Samuel Roze based on a JavaScript library by Raymond Hill.

## Requirements

PHP 8.2 or later.

## Installation

```bash
composer require benovermyer/voronoi
```

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
	  imagerectangle(
        $im,
        (int) round($point->x) - 2,
        (int) round($point->y) - 2,
        (int) round($point->x) + 2,
        (int) round($point->y) + 2,
        $black
    );
}

// Draw polygons
foreach ($diagram['cells'] as $cell) {
	$points = array();

	if (count($cell->half_edges) > 0) {
		$v = $cell->half_edges[0]->getStartPoint();
		if ($v) {
			$points[] = (int) round($v->x);
			$points[] = (int) round($v->y);
		}

		for ($i = 0; $i < count($cell->half_edges); $i++) {
			$halfedge = $cell->half_edges[$i];
			$edge = $halfedge->edge;

			if ($edge->va && $edge->vb) {
				imageline(
					$im,
					(int) round($edge->va->x),
					(int) round($edge->va->y),
					(int) round($edge->vb->x),
					(int) round($edge->vb->y),
					$red
				);
			}

			$v = $halfedge->getEndPoint();
			if ($v) {
				$points[] = (int) round($v->x);
				$points[] = (int) round($v->y);
			}
		}
	}

	// GD needs at least three vertices for a polygon
	if (count($points) < 6) {
		continue;
	}

	// Create polygon with a random color. PHP 8.1+ uses the three-argument form.
	$color = imagecolorallocatealpha($im, rand(0, 255), rand(0, 255), rand(0, 255), 50);
	imagefilledpolygon($im, $points, $color);
}

// Display image
imagepng($im, 'voronoi.png');
```

## Result metadata

`compute()` returns an array with the following keys:

- `sites`: the input sites (coordinates normalized to floats, and given an `id`)
- `cells`: the cells, indexed by site id
- `edges`: the diagram edges
- `execTime`: the computation duration **in seconds**

## Testing

```bash
composer install
composer test
```

The suite runs on PHP 8.2, 8.3, 8.4 and 8.5 in CI. Deprecations, warnings and notices are treated as failures.
