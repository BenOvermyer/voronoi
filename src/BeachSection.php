<?php

namespace Voronoi;

class BeachSection
{
    public Point $site;
    public ?CircleEvent $circleEvent = null;

    /** Edge introduced by this beach section, if any. */
    public ?Edge $edge = null;

    // Red-black tree links. Unyped on purpose: the same RBTree stores both
    // BeachSection and CircleEvent nodes.
    public $previous = null;
    public $next = null;
    public $left = null;
    public $right = null;
    public $parent = null;
    public bool $red = false;

    public function __construct(Point $site)
    {
        $this->site = $site;
    }
}
