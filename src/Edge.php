<?php

namespace Voronoi;

/**
 * This class represents an edge between two vertices.
 *
 */
class Edge
{
    /**
     * Points.
     *
     * p1 = left point
     * p2 = right point
     */
    public $p1;
    public $p2;

    public $va = null;
    public $vb = null;

    public function __construct(Point $p1 = null, Point $p2 = null)
    {
        // On stocke les points
        $this->p1 = $p1;
        $this->p2 = $p2;
    }

    /**
     * See if two Edges are equal.
     * @param Edge $edge
     * @return bool
     */
    public function equals(Edge $edge): bool
    {
        return (($this->p1->equals($edge->p2) && $this->p2->equals($edge->p1))
            || ($this->p1->equals($edge->p1) && $this->p2->equals($edge->p2)));
    }

    public function setStartPoint($lSite, $rSite, $vertex)
    {
        if (!$this->va && !$this->vb) {
            $this->va = $vertex;
            $this->p1 = $lSite;
            $this->p2 = $rSite;
        } else if ($this->p1 === $rSite) {
            $this->vb = $vertex;
        } else {
            $this->va = $vertex;
        }
    }

    public function setEndPoint($lSite, $rSite, $vertex)
    {
        $this->setStartPoint($rSite, $lSite, $vertex);
    }
}