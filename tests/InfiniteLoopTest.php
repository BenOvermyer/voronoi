<?php

namespace Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Voronoi\Point;
use Voronoi\Voronoi;

#[Large]
final class InfiniteLoopTest extends TestCase
{
    /**
     * Test diagram creation without infinite loop.
     * The maximum execution time is 60 seconds (enforced by PHPUnit's time
     * limit for large tests).
     *
     * @param int width Width of diagram
     * @param int height Height of diagram
     * @param array points The diagram points
     */
    #[DataProvider('provider')]
    public function testVoronoi(int $width, int $height, array $basicPoints): void
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

        $this->assertIsArray($diagram);
        $this->assertNotEmpty($diagram['cells']);
        $this->assertNotEmpty($diagram['edges']);
    }

    /**
     * Read the CSV data file.
     *
     * @return array
     */
    public static function provider(): array
    {
        // Read points
        $file = file(__DIR__ . '/data/issue4.dat');
        $points = [];
        foreach ($file as $line) {
            [$x, $y] = explode(',', trim($line));

            $points[] = [(float) $x, (float) $y];
        }

        return [
            [400, 400, $points],
        ];
    }
}
