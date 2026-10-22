<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Voronoi\Exception\NurbsException;
use Voronoi\Nurbs;
use Voronoi\Point;

final class NurbsTest extends TestCase
{
    public function testAddPointsAppendsEveryBatch(): void
    {
        $nurbs = Nurbs::fromPoints([new Point(0, 0, 1), new Point(1, 0, 2)]);
        $nurbs->addPoints([new Point(0, 1, 3), new Point(1, 1, 4)]);

        $this->assertCount(4, $nurbs->getPoints());
        $this->assertSame(1, $nurbs->getPoints()[0]->z);
        $this->assertSame(4, $nurbs->getPoints()[3]->z);
    }

    public function testExactMatchReturnsTheKnownPoint(): void
    {
        $nurbs = Nurbs::fromPoints([new Point(0, 0, 10), new Point(5, 5, 20)]);

        $this->assertEqualsWithDelta(20.0, $nurbs->getPoint(5, 5)->z, 1e-9);
    }

    public function testEquidistantSamplesAreAllRetained(): void
    {
        // Four samples, all at a distance of 2 from the query, with distinct
        // elevations. If any of them is dropped the average changes.
        $nurbs = Nurbs::fromPoints([
            new Point(0, 2, 10),
            new Point(2, 0, 20),
            new Point(0, -2, 30),
            new Point(-2, 0, 40),
        ]);

        $this->assertEqualsWithDelta(25.0, $nurbs->getPoint(0, 0, 4)->z, 1e-9);
    }

    public function testEuclideanNearestNeighbourWinsOverManhattan(): void
    {
        // Euclidean: (3, 3) is closer (4.24 vs 5.0).
        // Manhattan: (5, 0) is closer (5 vs 6).
        $nurbs = Nurbs::fromPoints([
            new Point(3, 3, 10),
            new Point(5, 0, 20),
        ]);

        $this->assertEqualsWithDelta(10.0, $nurbs->getPoint(0, 0, 1)->z, 1e-9);
    }

    public function testFractionalCoordinatesNearTheQuery(): void
    {
        $nurbs = Nurbs::fromPoints([
            new Point(1.1, 1.1, 10),
            new Point(1.2, 1.0, 20),
            new Point(1.0, 1.3, 30),
        ]);

        $point = $nurbs->getPoint(1, 1, 3);

        $this->assertSame(1, $point->x);
        $this->assertSame(1, $point->y);
        $this->assertIsFloat($point->z);
        $this->assertTrue(is_finite($point->z));
    }

    public function testEmptySurfaceIsRejected(): void
    {
        $this->expectException(NurbsException::class);

        Nurbs::fromPoints([])->getPoint(1, 1);
    }

    public function testDelaunayInterpolatesAPlanarSurface(): void
    {
        // Plane z = 2x + 3y + 5.
        $nurbs = Nurbs::fromPoints([
            new Point(0, 0, 5),
            new Point(10, 0, 25),
            new Point(0, 10, 35),
            new Point(10, 10, 55),
        ]);
        $nurbs->setCalculationMethod(Nurbs::CALCULATE_DELAUNAY);

        $this->expectOutputString('');

        $this->assertEqualsWithDelta(15.0, $nurbs->getPoint(2, 2)->z, 1e-9);
        $this->assertEqualsWithDelta(30.0, $nurbs->getPoint(5, 5)->z, 1e-9);
    }

    public function testDelaunayQueryOutsideTheSurfaceIsRejected(): void
    {
        $nurbs = Nurbs::fromPoints([
            new Point(0, 0, 5),
            new Point(10, 0, 25),
            new Point(0, 10, 35),
        ]);
        $nurbs->setCalculationMethod(Nurbs::CALCULATE_DELAUNAY);

        $this->expectException(NurbsException::class);

        $nurbs->getPoint(100, 100);
    }

    public function testDelaunayTriangulationIsCached(): void
    {
        $nurbs = Nurbs::fromPoints([
            new Point(0, 0, 0),
            new Point(10, 0, 10),
            new Point(0, 10, 10),
            new Point(10, 10, 20),
        ]);

        $first = $nurbs->getDelaunaySurfaces();
        $second = $nurbs->getDelaunaySurfaces();

        $this->assertSame($first, $second);
        $this->assertSame($first[0], $second[0]);
    }

    public function testDelaunayCacheIsInvalidatedWhenPointsChange(): void
    {
        $nurbs = Nurbs::fromPoints([
            new Point(0, 0, 0),
            new Point(10, 0, 10),
            new Point(0, 10, 10),
        ]);

        $this->assertCount(1, $nurbs->getDelaunaySurfaces());

        $nurbs->addPoints([new Point(10, 10, 20)]);

        $this->assertCount(2, $nurbs->getDelaunaySurfaces());

        // Moving a point must also invalidate the cache, because Point
        // objects are mutable.
        $before = $nurbs->getDelaunaySurfaces();
        $nurbs->getPoints()[0]->x = 20.0;
        $after = $nurbs->getDelaunaySurfaces();

        $this->assertNotSame($before, $after);
    }

    public function testDelaunayRequiresAtLeastThreePoints(): void
    {
        $this->expectException(NurbsException::class);

        Nurbs::fromPoints([new Point(0, 0, 0), new Point(1, 1, 1)])->getDelaunaySurfaces();
    }
}
