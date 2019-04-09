<?php

namespace Spaceland\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;

class ClassCatcher extends NodeVisitorAbstract
{
    /**
     * @var array<string>
     */
    private $classes;

    public function __construct()
    {
        $this->classes = [];
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof Class_) {
            if ($node->name) {
                $this->classes[] = (string) $node->namespacedName;
            }
        }
        if ($node instanceof Interface_) {
            if ($node->name) {
                $this->classes[] = (string) $node->namespacedName;
            }
        }
    }

    public function definedClasses()
    {
        return $this->classes;
    }
}
