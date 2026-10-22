<?php

namespace Tests;

use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use Tests\Support\GeometryAssertions;
use Voronoi\Exception\VoronoiException;
use Voronoi\Point;
use Voronoi\Voronoi;

#[Medium]
final class VoronoiTest extends TestCase
{
    use GeometryAssertions;

    public function testEmptyInputProducesAnEmptyDiagram(): void
    {
        $diagram = (new Voronoi())->compute([], $this->boundingBox());

        $this->assertSame([], $diagram['cells']);
        $this->assertSame([], $diagram['edges']);
    }

    public function testSingleSiteFillsTheBoundingBox(): void
    {
        $bbox = $this->boundingBox();
        $diagram = (new Voronoi())->compute([new Point(200, 200)], $bbox);

        $this->assertCount(1, $diagram['cells']);

        $cell = $diagram['cells'][0];
        $this->assertCount(4, $cell->half_edges);
        $this->assertTrue($this->isCellClosed($cell));
        $this->assertEqualsWithDelta(400 * 400, $this->cellArea($cell), 1e-6);
    }

    public function testRepeatedCopiesOfASingleSiteFillTheBoundingBox(): void
    {
        $bbox = $this->boundingBox();
        $diagram = (new Voronoi())->compute(
            [new Point(200, 200), new Point(200, 200), new Point(200, 200)],
            $bbox
        );

        $this->assertCount(1, $diagram['cells']);
        $this->assertEqualsWithDelta(400 * 400, $this->cellArea($diagram['cells'][0]), 1e-6);
    }

    public function testInstanceReuseDoesNotLeakPreviousState(): void
    {
        $voronoi = new Voronoi();

        $first = $voronoi->compute(
            [new Point(10, 10), new Point(100, 50), new Point(50, 100), new Point(200, 200)],
            $this->boundingBox()
        );
        $this->assertCount(4, $first['cells']);

        // An empty second computation must be empty, not the previous diagram.
        $empty = $voronoi->compute([], $this->boundingBox());
        $this->assertSame([], $empty['cells']);
        $this->assertSame([], $empty['edges']);

        // The diagram returned earlier must not have been mutated.
        $this->assertCount(4, $first['cells']);

        $second = $voronoi->compute([new Point(20, 20), new Point(80, 300)], $this->boundingBox());
        $this->assertCount(2, $second['cells']);
    }

    public function testInstanceReuseWithNewSitesDoesNotHang(): void
    {
        $voronoi = new Voronoi();
        $voronoi->compute(
            [new Point(10, 10), new Point(100, 50), new Point(50, 100), new Point(200, 200)],
            $this->boundingBox()
        );

        $second = $voronoi->compute([new Point(20, 20), new Point(80, 300)], $this->boundingBox());

        $this->assertCount(2, $second['cells']);
    }

    public function testDuplicateSitesAreDeduplicated(): void
    {
        $diagram = (new Voronoi())->compute(
            [new Point(10, 10), new Point(10, 10), new Point(300, 300)],
            $this->boundingBox()
        );

        $this->assertCount(2, $diagram['cells']);
    }

    public function testNumericTypesDoNotBypassDuplicateDetection(): void
    {
        $diagram = (new Voronoi())->compute(
            [new Point(10, 10), new Point(10.0, 10.0), new Point(300, 300)],
            $this->boundingBox()
        );

        $this->assertCount(2, $diagram['cells']);
    }

    public function testMixedIntegerAndFloatCoordinatesWithEqualHeights(): void
    {
        $diagram = (new Voronoi())->compute(
            [new Point(10, 10), new Point(300, 10.0), new Point(150, 300)],
            $this->boundingBox()
        );

        $this->assertCount(3, $diagram['cells']);
        $this->assertEqualsWithDelta(160000.0, $this->totalArea($diagram['cells']), 0.01);
    }

    public function testNumericStringCoordinatesAreSupported(): void
    {
        $diagram = (new Voronoi())->compute(
            [new Point('10', '20'), new Point('10', '20'), new Point(300, 300)],
            $this->boundingBox()
        );

        $this->assertCount(2, $diagram['cells']);
        $this->assertIsFloat($diagram['sites'][0]->x);
    }

    public function testInvalidCoordinatesAreRejected(): void
    {
        $this->expectException(VoronoiException::class);

        (new Voronoi())->compute([new Point('not-a-number', 10)], $this->boundingBox());
    }

    public function testNonPointSitesAreRejected(): void
    {
        $this->expectException(VoronoiException::class);

        (new Voronoi())->compute(['nope'], $this->boundingBox());
    }

    #[Large]
    public function testCellsCloseAndCoverTheBoundingBox(): void
    {
        mt_srand(20241022);

        $sites = [];
        for ($i = 0; $i < 200; $i++) {
            $sites[] = new Point(mt_rand(1, 399), mt_rand(1, 399));
        }

        $bbox = $this->boundingBox();
        $diagram = (new Voronoi())->compute($sites, $bbox);

        $this->assertNotEmpty($diagram['cells']);

        foreach ($diagram['cells'] as $cell) {
            $this->assertTrue($this->isCellClosed($cell), 'Every cell must be a closed polygon');
        }

        $this->assertEqualsWithDelta(160000.0, $this->totalArea($diagram['cells']), 0.01);
    }

    public function testExecTimeIsReportedInSeconds(): void
    {
        mt_srand(7);

        $sites = [];
        for ($i = 0; $i < 500; $i++) {
            $sites[] = new Point(mt_rand(1, 999), mt_rand(1, 999));
        }

        $bbox = $this->boundingBox(1000, 1000);

        $start = microtime(true);
        $diagram = (new Voronoi())->compute($sites, $bbox);
        $wall = microtime(true) - $start;

        $this->assertGreaterThan(0.0, $diagram['execTime']);
        $this->assertLessThan(1.0, $diagram['execTime']);

        // The previous implementation divided an already-seconds value by 1000,
        // reporting a duration 1000 times too small.
        $this->assertGreaterThan($wall * 0.1, $diagram['execTime']);
    }

    private function totalArea(array $cells): float
    {
        $area = 0.0;
        foreach ($cells as $cell) {
            $area += $this->cellArea($cell);
        }

        return $area;
    }
}
