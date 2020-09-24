<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Voronoi\Voronoi;
use Voronoi\Point;

final class InfiniteLoopTest extends TestCase
{
    /**
     * Test diagram creation without infinite loop.
     * The maximum execution time is 60 seconds.
     *
     * @param int width Width of diagram
     * @param int height Height of diagram
     * @param array points The diagram points
     *
     * @dataProvider provider
     */
    public function testVoronoi(int $width, int $height, array $basicPoints)
    {
        // Create border box
        $bbox = new \stdClass();
        $bbox->xl = 0;
        $bbox->xr = $width;
        $bbox->yt = 0;
        $bbox->yb = $height;

        // Create points
        $points = [];
        foreach ($basicPoints as $basic_point) {
            $points[] = new Point($basic_point[0], $basic_point[1]);
        }

        // Create diagram
        $voronoi = new Voronoi();
        $diagram = $voronoi->compute($points, $bbox);

        $this->assertTrue(is_array($diagram));
    }

    /**
     * Read the CSV data file.
     *
     * @return array
     */
    public function provider()
    {
        // Read points
        $file = file(getcwd() . '/tests/data/issue4.dat');
        $points = [];
        foreach ($file as $line) {
            [$x, $y] = explode(',', trim($line));

            $points[] = [$x, $y];
        }

        return [
            [400, 400, $points],
        ];
    }
}