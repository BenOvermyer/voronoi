<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Voronoi\Point;
use Voronoi\Vector;

final class VectorTest extends TestCase
{
    public function testZeroElevationIsAValidCoordinate(): void
    {
        $vector = Vector::fromPoints(new Point(0, 0, 0), new Point(1, 1, 5));

        $this->assertSame(5, $vector->z);
    }

    public function testMissingElevationStaysNull(): void
    {
        $vector = Vector::fromPoints(new Point(0, 0), new Point(1, 1, 5));

        $this->assertNull($vector->z);
    }

    public function testCrossProductWithZeroElevation(): void
    {
        $vector = Vector::fromPoints(new Point(0, 0, 0), new Point(1, 1, 5));
        $other = Vector::fromPoints(new Point(0, 0, 0), new Point(1, 0, 0));

        $product = $vector->vectorProduct($other);

        // (1, 1, 5) x (1, 0, 0) = (0, 5, -1)
        $this->assertSame(0, $product->x);
        $this->assertSame(5, $product->y);
        $this->assertSame(-1, $product->z);
    }

    public function testScalarProductIgnoresMissingElevation(): void
    {
        $a = Vector::fromPoints(new Point(0, 0), new Point(2, 3));
        $b = Vector::fromPoints(new Point(0, 0), new Point(1, 1));

        $this->assertSame(5.0, $a->scalarProduct($b));
    }

    public function testPointEqualityTreatsZeroElevationAsPresent(): void
    {
        $withZero = new Point(1, 1, 0);
        $without = new Point(1, 1);

        $this->assertFalse($withZero->equals($without));
        $this->assertTrue($withZero->equals(new Point(1, 1, 0)));
    }

    public function testToStringOmitsOnlyMissingElevation(): void
    {
        $this->assertSame('(1,2,0)', (string) new Point(1, 2, 0));
        $this->assertSame('(1,2)', (string) new Point(1, 2));
    }
}
