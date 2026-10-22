<?php

namespace Voronoi;

use Voronoi\Exception\NurbsException;

/**
 * Represents a Delaunay triangle
 *
 */
class Triangle extends AbstractSurface
{
    /**
     * Absolute tolerance used for the geometric predicates.
     */
    private const EPSILON = 1e-12;

    /**
     * We store the points of the triangle.
     *
     */
    public $p1;
    public $p2;
    public $p3;

    /**
     * Create the triangle, with three points.
     * @param Point $p1
     * @param Point $p2
     * @param Point $p3
     */
    public function __construct(Point $p1, Point $p2, Point $p3)
    {
        // We add the points to the surface.
        $this->addPoints([$p1, $p2, $p3]);

        // They are also stored locally
        $this->p1 = $p1;
        $this->p2 = $p2;
        $this->p3 = $p3;
    }

    /**
     * Twice the signed area of the triangle (the orientation determinant).
     */
    private function orientation(): float
    {
        return ($this->p2->x - $this->p1->x) * ($this->p3->y - $this->p1->y)
            - ($this->p2->y - $this->p1->y) * ($this->p3->x - $this->p1->x);
    }

    /**
     * Tolerance scaled to the size of the triangle, so that small but valid
     * triangles are not rejected while exactly collinear ones are.
     */
    private function orientationTolerance(): float
    {
        $length1 = abs($this->p2->x - $this->p1->x) + abs($this->p2->y - $this->p1->y);
        $length2 = abs($this->p3->x - $this->p1->x) + abs($this->p3->y - $this->p1->y);

        return self::EPSILON * max(1.0, $length1) * max(1.0, $length2);
    }

    /**
     * Check that the triangle is valid.
     *
     * A triangle is valid when its three vertices are not collinear, in any
     * direction. The previous implementation only rejected triangles whose
     * y coordinates were all within 1 unit of each other.
     */
    public function isValid(): bool
    {
        return abs($this->orientation()) > $this->orientationTolerance();
    }

    /**
     * Test if the given point is in the circumscribed circle of the triangle
     * @param Point $point
     * @return bool
     */
    public function pointInCircle(Point $point): bool
    {
        // We check that the triangle is valid
        if (!$this->isValid()) {
            return false;
        }

        // Circumcenter via the standard determinant formula. Unlike the
        // slope-based version this cannot divide by zero for horizontal,
        // vertical or diagonal edges.
        $ax = (float) $this->p1->x;
        $ay = (float) $this->p1->y;
        $bx = (float) $this->p2->x;
        $by = (float) $this->p2->y;
        $cx = (float) $this->p3->x;
        $cy = (float) $this->p3->y;

        $d = 2 * ($ax * ($by - $cy) + $bx * ($cy - $ay) + $cx * ($ay - $by));

        if (abs($d) <= self::EPSILON) {
            return false;
        }

        $a2 = $ax * $ax + $ay * $ay;
        $b2 = $bx * $bx + $by * $by;
        $c2 = $cx * $cx + $cy * $cy;

        $xc = ($a2 * ($by - $cy) + $b2 * ($cy - $ay) + $c2 * ($ay - $by)) / $d;
        $yc = ($a2 * ($cx - $bx) + $b2 * ($ax - $cx) + $c2 * ($bx - $ax)) / $d;

        $dx = $bx - $xc;
        $dy = $by - $yc;
        $rsqr = $dx * $dx + $dy * $dy;

        $dx = $point->x - $xc;
        $dy = $point->y - $yc;
        $drsqr = $dx * $dx + $dy * $dy;

        // Small relative tolerance so that points on the circle count as
        // inside; cocircular input otherwise becomes order-dependent.
        return $drsqr <= $rsqr + self::EPSILON * max(1.0, $rsqr);
    }

    /**
     * See if the point is included in the triangle.
     *
     * Note: uses the barycenter technique.
     *
     * @return bool
     */
    public function pointIn(Point $point)
    {
        // The test is done on the (x, y) projection: elevations must not
        // influence whether a query belongs to the triangle.
        $v0x = $this->p3->x - $this->p1->x;
        $v0y = $this->p3->y - $this->p1->y;
        $v1x = $this->p2->x - $this->p1->x;
        $v1y = $this->p2->y - $this->p1->y;
        $v2x = $point->x - $this->p1->x;
        $v2y = $point->y - $this->p1->y;

        // We calculate the scalar product of the vectors
        $dot00 = $v0x * $v0x + $v0y * $v0y;
        $dot01 = $v0x * $v1x + $v0y * $v1y;
        $dot02 = $v0x * $v2x + $v0y * $v2y;
        $dot11 = $v1x * $v1x + $v1y * $v1y;
        $dot12 = $v1x * $v2x + $v1y * $v2y;

        // We calculate the coordinates of the barycenter
        $denominator = $dot00 * $dot11 - $dot01 * $dot01;
        if (abs($denominator) <= self::EPSILON) {
            // Degenerate triangle: no interior to test against.
            return false;
        }

        $invDenom = 1 / $denominator;
        $u = ($dot11 * $dot02 - $dot01 * $dot12) * $invDenom;
        $v = ($dot00 * $dot12 - $dot01 * $dot02) * $invDenom;

        // We check that the point is in the triangle. The tolerance makes
        // points exactly on an edge belong to the triangle, which matters
        // when a query lies on a shared Delaunay edge.
        return ($u >= -self::EPSILON && $v >= -self::EPSILON
            && (($u + $v) <= 1 + self::EPSILON));
    }

    /**
     * Returns a rectangle enclosing the triangle.
     *
     * @return array
     */
    public function getRect()
    {
        $x1 = min([$this->p1->x, $this->p2->x, $this->p3->x]);
        $y1 = min([$this->p1->y, $this->p2->y, $this->p3->y]);
        $x2 = max([$this->p1->x, $this->p2->x, $this->p3->x]);
        $y2 = max([$this->p1->y, $this->p2->y, $this->p3->y]);

        return [new Point($x1, $y1), new Point($x2, $y2)];
    }

    /**
     * Get the point at coordinates (x, y) on the plane.
     *
     * The signature stays compatible with AbstractSurface. The optional
     * radius is irrelevant here: a triangle defines a single plane.
     *
     * @see http://fr.wikipedia.org/wiki/Plan_%28math%C3%A9matiques%29#D.C3.A9finition_par_un_vecteur_normal_et_un_point
     *
     * @param int $x
     * @param int $y
     * @param float $radius
     * @return Point
     * @throws NurbsException
     */
    public function getPoint(int $x, int $y, float $radius = 3): Point
    {
        $point = new Point($x, $y, 0);

        // We are looking for the vector normal to the plane of the triangle
        $v_triangle_normal = $this->getNormalVector();

        // A vertical plane has no unique elevation for a given (x, y).
        if (abs($v_triangle_normal->z) <= self::EPSILON) {
            throw new NurbsException('The triangle defines a vertical plane: impossible to interpolate an elevation');
        }

        // We calculate the value "d" of the equation of the plane
        $d = -($v_triangle_normal->x * $this->p1->x + $v_triangle_normal->y * $this->p1->y + $v_triangle_normal->z * ($this->p1->z ?? 0));

        // Thanks to the equation of the plane, we calculate the value of Z
        $point->z = (-$v_triangle_normal->x * $point->x - $v_triangle_normal->y * $point->y - $d) / ($v_triangle_normal->z);

        return $point;
    }

    /**
     * Returns the vector normal to the plane defined by the triangle.
     *
     * @see http://fr.wikipedia.org/wiki/Plan_%28math%C3%A9matiques%29#D.C3.A9finition_par_deux_vecteurs_et_un_point
     *
     * @return Vector
     */
    public function getNormalVector()
    {
        // We will calculate two vectors starting from A
        $v1 = Vector::fromPoints($this->p2, $this->p1);
        $v2 = Vector::fromPoints($this->p3, $this->p1);

        // We will now calculate the normal vector using the cross product
        return $v2->vectorProduct($v1);
    }
}
