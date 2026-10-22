<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Voronoi\AbstractSurface;
use Voronoi\Exception\NurbsException;
use Voronoi\Point;
use Voronoi\Triangle;

final class TriangleTest extends TestCase
{
    public function testTriangleCanBeLoadedAndConstructed(): void
    {
        $triangle = new Triangle(new Point(0, 0), new Point(1, 0), new Point(0, 1));

        $this->assertInstanceOf(AbstractSurface::class, $triangle);
    }

    public function testGetPointSignatureIsCompatibleWithTheParent(): void
    {
        $method = new ReflectionMethod(Triangle::class, 'getPoint');

        $this->assertSame(3, $method->getNumberOfParameters());
        $this->assertTrue($method->getParameters()[2]->isOptional());
    }

    public function testGetPointThroughTheParentApi(): void
    {
        $triangle = new Triangle(new Point(0, 0, 0), new Point(10, 0, 10), new Point(0, 10, 10));

        // Calls made through the AbstractSurface signature must keep working.
        $this->assertEqualsWithDelta(4.0, $triangle->getPoint(2, 2, 3)->z, 1e-9);
    }

    public function testUnitTriangleIsValid(): void
    {
        $triangle = new Triangle(new Point(0, 0), new Point(1, 0), new Point(0, 1));

        $this->assertTrue($triangle->isValid());
    }

    public function testSmallTriangleIsValid(): void
    {
        $triangle = new Triangle(new Point(0, 0), new Point(0.5, 0.2), new Point(0.2, 0.5));

        $this->assertTrue($triangle->isValid());
    }

    public function testHorizontalCollinearPointsAreInvalid(): void
    {
        $this->assertFalse((new Triangle(new Point(0, 0), new Point(1, 0), new Point(2, 0)))->isValid());
    }

    public function testVerticalCollinearPointsAreInvalid(): void
    {
        $this->assertFalse((new Triangle(new Point(0, 0), new Point(0, 1), new Point(0, 2)))->isValid());
    }

    public function testDiagonalCollinearPointsAreInvalid(): void
    {
        $this->assertFalse((new Triangle(new Point(0, 0), new Point(1, 1), new Point(2, 2)))->isValid());
    }

    public function testDuplicateVerticesAreInvalid(): void
    {
        $this->assertFalse((new Triangle(new Point(1, 1), new Point(1, 1), new Point(5, 5)))->isValid());
    }

    public function testPointInCircleMembership(): void
    {
        $triangle = new Triangle(new Point(0, 0), new Point(1, 0), new Point(0, 1));

        // The circumcircle of this triangle is centred on (0.5, 0.5) with r = sqrt(0.5).
        $this->assertTrue($triangle->pointInCircle(new Point(0.2, 0.2)));
        $this->assertFalse($triangle->pointInCircle(new Point(2, 2)));
    }

    public function testPointInCircleOnCollinearTriangleReturnsFalse(): void
    {
        $triangle = new Triangle(new Point(0, 0), new Point(1, 1), new Point(2, 2));

        $this->assertFalse($triangle->pointInCircle(new Point(1, 1)));
    }

    public function testPointInIncludesTheEdges(): void
    {
        $triangle = new Triangle(new Point(0, 0), new Point(10, 0), new Point(0, 10));

        $this->assertTrue($triangle->pointIn(new Point(5, 0)));
        $this->assertTrue($triangle->pointIn(new Point(1, 1)));
        $this->assertFalse($triangle->pointIn(new Point(9, 9)));
    }

    public function testGetPointInterpolatesAPlanarSurfaceWithoutOutput(): void
    {
        // Plane z = 2x + 3y + 5.
        $triangle = new Triangle(new Point(0, 0, 5), new Point(10, 0, 25), new Point(0, 10, 35));

        $this->expectOutputString('');

        $point = $triangle->getPoint(2, 2);

        $this->assertSame(2, $point->x);
        $this->assertSame(2, $point->y);
        $this->assertEqualsWithDelta(15.0, $point->z, 1e-9);
    }

    public function testGetPointOnAVerticalPlaneIsRejected(): void
    {
        $triangle = new Triangle(new Point(0, 0, 0), new Point(1, 0, 0), new Point(0, 0, 1));

        $this->expectException(NurbsException::class);

        $triangle->getPoint(1, 1);
    }

    public function testGetRectEnclosesTheTriangle(): void
    {
        $triangle = new Triangle(new Point(1, 5), new Point(4, 2), new Point(0, 3));
        [$min, $max] = $triangle->getRect();

        $this->assertSame(0, $min->x);
        $this->assertSame(2, $min->y);
        $this->assertSame(4, $max->x);
        $this->assertSame(5, $max->y);
    }
}
