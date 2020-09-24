<?php

namespace Voronoi;

/**
 * Implementation of Red-Black tree.
 *
 * Based on C version of "rbtree" by Franck Bui-Huu
 * https://github.com/fbuihuu/libtree/blob/master/rb.c
 *
 * This tree implementation provides access to elements with a performance of O (log (n)), which is the best possible performance.
 *
 * @see http://en.wikipedia.org/wiki/Red-black_tree
 */
class RBTree
{
    public $root = null;

    /**
     * Insert a successor
     *
     * @param
     */
    public function insertSuccessor($node, $successor)
    {

        $parent = null;

        if ($node) {
            // >>> rhill 2011-05-27: Performance: cache previous/next nodes
            $successor->previous = $node;
            $successor->next = $node->next;

            if ($node->next) {
                $node->next->previous = $successor;
            }

            $node->next = $successor;
            // <<<

            if ($node->right) {
                // in-place expansion of node->right.getFirst();
                $node = $node->right;

                while ($node->left) {
                    $node = $node->left;
                }

                $node->left = $successor;
            } else {
                $node->right = $successor;
            }

            $parent = $node;
        }

        // rhill 2011-06-07: if node is null, successor must be inserted
        // to the left-most part of the tree
        else if ($this->root) {
            $node = $this->getFirst($this->root);

            // >>> Performance: cache previous/next nodes
            $successor->previous = null;
            $successor->next = $node;
            $node->previous = $successor;

            // <<<
            $node->left = $successor;
            $parent = $node;
        } else {
            // >>> Performance: cache previous/next nodes
            $successor->previous = $successor->next = null;
            // <<<
            $this->root = $successor;
            $parent = null;
        }

        $successor->left = $successor->right = null;
        $successor->parent = $parent;
        $successor->red = true;

        // Fixup the modified tree by recoloring nodes and performing
        // rotations (2 at most) hence the red-black tree properties are
        // preserved.
        $grandpa = $uncle = null;
        $node = $successor;

        while ($parent && $parent->red) {
            $grandpa = $parent->parent;

            if ($parent === $grandpa->left) {
                $uncle = $grandpa->right;
                if ($uncle && $uncle->red) {
                    $parent->red = $uncle->red = false;
                    $grandpa->red = true;
                    $node = $grandpa;
                } else {
                    if ($node === $parent->right) {
                        $this->rotateLeft($parent);
                        $node = $parent;
                        $parent = $node->parent;
                    }

                    $parent->red = false;
                    $grandpa->red = true;

                    $this->rotateRight($grandpa);
                }
            } else {
                $uncle = $grandpa->left;
                if ($uncle && $uncle->red) {
                    $parent->red = $uncle->red = false;
                    $grandpa->red = true;
                    $node = $grandpa;
                } else {
                    if ($node === $parent->left) {
                        $this->rotateRight($parent);
                        $node = $parent;
                        $parent = $node->parent;
                    }

                    $parent->red = false;
                    $grandpa->red = true;

                    $this->rotateLeft($grandpa);
                }
            }

            $parent = $node->parent;
        }

        $this->root->red = false;
    }

    public function removeNode($node)
    {
        // >>> rhill 2011-05-27: Performance: cache previous/next nodes
        if ($node->next) {
            $node->next->previous = $node->previous;
        }

        if ($node->previous) {
            $node->previous->next = $node->next;
        }

        $node->next = $node->previous = null;
        // <<<

        $parent = $node->parent;
        $left = $node->left;
        $right = $node->right;
        $next = null;

        if (!$left) {
            $next = $right;
        } else if (!$right) {
            $next = $left;
        } else {
            $next = $this->getFirst($right);
        }

        if ($parent) {
            if ($parent->left === $node) {
                $parent->left = $next;
            } else {
                $parent->right = $next;
            }
        } else {
            $this->root = $next;
        }

        // enforce red-black rules
        $isRed = null;

        if ($left && $right) {
            $isRed = $next->red;
            $next->red = $node->red;
            $next->left = $left;
            $left->parent = $next;

            if ($next !== $right) {
                $parent = $next->parent;
                $next->parent = $node->parent;
                $node = $next->right;
                $parent->left = $node;
                $next->right = $right;
                $right->parent = $next;
            } else {
                $next->parent = $parent;
                $parent = $next;
                $node = $next->right;
            }
        } else {
            $isRed = $node->red;
            $node = $next;
        }

        // 'node' is now the sole successor's child and 'parent' its
        // new parent (since the successor can have been moved)
        if ($node) {
            $node->parent = $parent;
        }

        // the 'easy' cases
        if ($isRed) {
            return;
        }

        if ($node && $node->red) {
            $node->red = false;

            return;
        }

        // the other cases
        $sibling = null;

        do {
            if ($node === $this->root) {
                break;
            }

            if ($node === $parent->left) {
                $sibling = $parent->right;

                if ($sibling->red) {
                    $sibling->red = false;
                    $parent->red = true;
                    $this->rotateLeft($parent);
                    $sibling = $parent->right;
                }

                if (($sibling->left && $sibling->left->red) || ($sibling->right && $sibling->right->red)) {
                    if (!$sibling->right || !$sibling->right->red) {
                        $sibling->left->red = false;
                        $sibling->red = true;
                        $this->rotateRight($sibling);
                        $sibling = $parent->right;
                    }

                    $sibling->red = $parent->red;
                    $parent->red = $sibling->right->red = false;
                    $this->rotateLeft($parent);
                    $node = $this->root;

                    break;
                }

            } else {
                $sibling = $parent->left;
                if ($sibling->red) {
                    $sibling->red = false;
                    $parent->red = true;
                    $this->rotateRight($parent);
                    $sibling = $parent->left;
                }

                if (($sibling->left && $sibling->left->red) || ($sibling->right && $sibling->right->red)) {
                    if (!$sibling->left || !$sibling->left->red) {
                        $sibling->right->red = false;
                        $sibling->red = true;
                        $this->rotateLeft($sibling);
                        $sibling = $parent->left;
                    }

                    $sibling->red = $parent->red;
                    $parent->red = $sibling->left->red = false;
                    $this->rotateRight($parent);
                    $node = $this->root;

                    break;
                }
            }

            $sibling->red = true;
            $node = $parent;
            $parent = $parent->parent;
        } while (!$node->red);

        if ($node) {
            $node->red = false;
        }
    }

    public function rotateLeft($node)
    {
        $p = $node;
        $q = $node->right; // can't be null
        $parent = $p->parent;

        if ($parent) {
            if ($parent->left === $p) {
                $parent->left = $q;
            } else {
                $parent->right = $q;
            }
        } else {
            $this->root = $q;
        }

        $q->parent = $parent;
        $p->parent = $q;
        $p->right = $q->left;

        if ($p->right) {
            $p->right->parent = $p;
        }

        $q->left = $p;
    }

    public function rotateRight($node)
    {
        $p = $node;
        $q = $node->left; // can't be null
        $parent = $p->parent;

        if ($parent) {
            if ($parent->left === $p) {
                $parent->left = $q;
            } else {
                $parent->right = $q;
            }
        } else {
            $this->root = $q;
        }

        $q->parent = $parent;
        $p->parent = $q;
        $p->left = $q->right;
        if ($p->left) {
            $p->left->parent = $p;
        }

        $q->right = $p;
    }

    public function getFirst($node)
    {
        while ($node->left != null) {
            $node = $node->left;
        }

        return $node;
    }

    public function getLast($node)
    {
        while ($node->right) {
            $node = $node->right;
        }

        return $node;
    }
}