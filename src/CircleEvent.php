<?php

namespace Voronoi;

class CircleEvent
{
    /** Beach section whose disappearance triggers this event. */
    public ?BeachSection $arc = null;

    /** Site of the arc below the event. */
    public ?Point $site = null;

    /** Event coordinates. */
    public ?float $x = null;
    public ?float $y = null;
    public ?float $ycenter = null;

    // Red-black tree links. Unyped on purpose: the same RBTree stores both
    // BeachSection and CircleEvent nodes.
    public $previous = null;
    public $next = null;
    public $left = null;
    public $right = null;
    public $parent = null;
    public bool $red = false;
}
