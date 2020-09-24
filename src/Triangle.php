<?php

namespace Voronoi;

/**
 * Represents a Delaunay triangle
 *
 */
class Triangle extends AbstractSurface
{
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
     * Check that the triangle is valid.
     *
     */
    public function isValid(): bool
    {
        $epsilon = pow(1, -15);

        return !(abs($this->p1->y - $this->p2->y) < $epsilon && abs($this->p2->y - $this->p3->y) < $epsilon);
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

        // We calculate the rounded value "0"
        $epsilon = pow(1, -15);

        if (abs($this->p2->y - $this->p1->y) < $epsilon) {
            $m2 = -($this->p3->x - $this->p2->x) / ($this->p3->y - $this->p2->y);
            $mx2 = ($this->p2->x + $this->p3->x) * 0.5;
            $my2 = ($this->p2->y + $this->p3->y) * 0.5;

            //Calculate CircumCircle center (xc,yc)
            $xc = ($this->p2->x + $this->p1->x) * 0.5;
            $yc = $m2 * ($xc - $mx2) + $my2;
        } else if (abs($this->p3->y - $this->p2->y) < $epsilon) {
            $m1 = -($this->p2->x - $this->p1->x) / ($this->p2->y - $this->p1->y);
            $mx1 = ($this->p1->x + $this->p2->x) * 0.5;
            $my1 = ($this->p1->y + $this->p2->y) * 0.5;
            $xc = ($this->p3->x + $this->p2->x) * 0.5;
            $yc = $m1 * ($xc - $mx1) + $my1;
        } else {
            $m1 = -($this->p2->x - $this->p1->x) / ($this->p2->y - $this->p1->y);
            $m2 = -($this->p3->x - $this->p2->x) / ($this->p3->y - $this->p2->y);
            $mx1 = ($this->p1->x + $this->p2->x) * 0.5;
            $mx2 = ($this->p2->x + $this->p3->x) * 0.5;
            $my1 = ($this->p1->y + $this->p2->y) * 0.5;
            $my2 = ($this->p2->y + $this->p3->y) * 0.5;
            $xc = ($m1 * $mx1 - $m2 * $mx2 + $my2 - $my1) / ($m1 - $m2);
            $yc = $m1 * ($xc - $mx1) + $my1;
        }

        $dx = $this->p2->x - $xc;
        $dy = $this->p2->y - $yc;
        $rsqr = $dx * $dx + $dy * $dy;

        //double r = Math.Sqrt(rsqr); //Circumcircle radius
        $dx = $point->x - $xc;
        $dy = $point->y - $yc;
        $drsqr = $dx * $dx + $dy * $dy;

        return ($drsqr <= $rsqr);
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
        // We calculate the vectors
        $v0 = Vector::fromPoints($this->p3, $this->p1);
        $v1 = Vector::fromPoints($this->p2, $this->p1);
        $v2 = Vector::fromPoints($point, $this->p1);

        // We calculate the scalar product of the vectors
        $dot00 = $v0->scalarProduct($v0);
        $dot01 = $v0->scalarProduct($v1);
        $dot02 = $v0->scalarProduct($v2);
        $dot11 = $v1->scalarProduct($v1);
        $dot12 = $v1->scalarProduct($v2);

        // We calculate the coordinates of the barycenter
        $invDenom = 1 / ($dot00 * $dot11 - $dot01 * $dot01);
        $u = ($dot11 * $dot02 - $dot01 * $dot12) * $invDenom;
        $v = ($dot00 * $dot12 - $dot01 * $dot02) * $invDenom;

        // We check that the point is in the triangle
        return ($u > 0 && $v > 0 && (($u + $v) < 1));
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
     * @see http://fr.wikipedia.org/wiki/Plan_%28math%C3%A9matiques%29#D.C3.A9finition_par_un_vecteur_normal_et_un_point
     *
     * @param int $x
     * @param int $y
     * @return Point
     */
    public function getPoint(int $x, int $y): Point
    {
        $point = new Point($x, $y, 0);

        // We are looking for the vector normal to the plane of the triangle
        $v_triangle_normal = $this->getNormalVector();

        // We calculate the value "d" of the equation of the plane
        $d = -($v_triangle_normal->x * $this->p1->x + $v_triangle_normal->y * $this->p1->y + $v_triangle_normal->z * $this->p1->z);

        // Thanks to the equation of the plane, we calculate the value of Z
        $point->z = (-$v_triangle_normal->x * $point->x - $v_triangle_normal->y * $point->y - $d) / ($v_triangle_normal->z);
        echo $point->z . '/' . $d . "\n";

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