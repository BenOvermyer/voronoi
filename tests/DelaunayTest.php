<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Voronoi\Delaunay;
use Voronoi\Point;
use Voronoi\Triangle;

final class DelaunayTest extends TestCase
{
    public function testEmptyInputProducesNoTriangle(): void
    {
        $this->assertSame([], Delaunay::triangulate([]));
    }

    public function testThreeNonCollinearPointsProduceOneTriangle(): void
    {
        $triangles = Delaunay::triangulate([new Point(0, 0), new Point(10, 0), new Point(0, 10)]);

        $this->assertCount(1, $triangles);
        $this->assertInstanceOf(Triangle::class, $triangles[0]);
        $this->assertCount(3, $triangles[0]->getPoints());
    }

    public function testCollinearPointsProduceNoTriangle(): void
    {
        $triangles = Delaunay::triangulate([new Point(0, 0), new Point(1, 1), new Point(2, 2)]);

        $this->assertSame([], $triangles);
    }

    public function testIdenticalPointsProduceNoTriangle(): void
    {
        $triangles = Delaunay::triangulate([new Point(5, 5), new Point(5, 5), new Point(5, 5)]);

        $this->assertSame([], $triangles);
    }

    public function testTriangulationOfARandomSetIsValid(): void
    {
        mt_srand(42);

        $points = [];
        for ($i = 0; $i < 30; $i++) {
            $points[] = new Point(mt_rand(0, 100), mt_rand(0, 100), mt_rand(0, 50));
        }

        $triangles = Delaunay::triangulate($points);

        $this->assertNotEmpty($triangles);

        // Every triangle must be non-degenerate.
        foreach ($triangles as $triangle) {
            $this->assertTrue($triangle->isValid());
        }

        // Every input point must belong to at least one triangle.
        $covered = [];
        foreach ($triangles as $triangle) {
            foreach ([$triangle->p1, $triangle->p2, $triangle->p3] as $vertex) {
                $covered[$vertex->id] = true;
            }
        }
        foreach ($points as $point) {
            $this->assertArrayHasKey($point->id, $covered);
        }

        // No two triangles may be identical, and an undirected edge may be
        // shared by at most two triangles (once per adjacent triangle).
        $edgeCounts = [];
        $trianglesSeen = [];
        foreach ($triangles as $triangle) {
            $ids = [$triangle->p1->id, $triangle->p2->id, $triangle->p3->id];
            sort($ids);
            $key = implode(':', $ids);
            $this->assertArrayNotHasKey($key, $trianglesSeen, 'Duplicate triangle ' . $key);
            $trianglesSeen[$key] = true;

            $pairs = [
                [$triangle->p1->id, $triangle->p2->id],
                [$triangle->p2->id, $triangle->p3->id],
                [$triangle->p3->id, $triangle->p1->id],
            ];

            foreach ($pairs as [$a, $b]) {
                $edgeKey = $a < $b ? $a . ':' . $b : $b . ':' . $a;
                $edgeCounts[$edgeKey] = ($edgeCounts[$edgeKey] ?? 0) + 1;
            }
        }

        foreach ($edgeCounts as $edgeKey => $count) {
            $this->assertLessThanOrEqual(2, $count, 'Edge ' . $edgeKey . ' belongs to too many triangles');
        }
    }

    public function testDuplicatePointsDoNotBreakTriangulation(): void
    {
        $points = [
            new Point(0, 0), new Point(0, 0),
            new Point(10, 0),
            new Point(0, 10),
        ];

        $triangles = Delaunay::triangulate($points);

        $this->assertNotEmpty($triangles);
    }

    public function testCocircularPointsDoNotThrow(): void
    {
        // Four points on the unit circle are cocircular.
        $points = [
            new Point(1, 0), new Point(0, 1), new Point(-1, 0), new Point(0, -1),
        ];

        $triangles = Delaunay::triangulate($points);

        $this->assertNotEmpty($triangles);
    }
}
