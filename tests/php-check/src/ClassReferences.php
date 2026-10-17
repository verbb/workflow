<?php

namespace Verbb\PhpCheck;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

final class ClassReferences
{
    public function unresolved(array $sources, callable $exists, array $optional = []): array
    {
        $parsed = [];
        $declared = [];
        foreach ($sources as $path => $code) {
            $parsed[$path] = $this->inspect($code);
            foreach ($parsed[$path]['declarations'] as $name) {
                $declared[$name] = true;
            }
        }

        $errors = [];
        $resolved = [];
        foreach ($parsed as $path => $file) {
            foreach ($file['references'] as $reference) {
                $name = $reference['name'];
                $resolved[$name] ??= isset($declared[$name]) || $exists($name) || isset($optional[$name]);
                if (!$resolved[$name]) {
                    $errors[] = $path . ':' . $reference['line'] . ': ' . $name;
                }
            }
        }
        sort($errors);

        return array_values(array_unique($errors));
    }

    public function inspect(string $code): array
    {
        $nodes = (new ParserFactory())->createForNewestSupportedVersion()->parse($code);
        $collector = new class extends NodeVisitorAbstract {
            public array $references = [];
            public array $declarations = [];

            public function leaveNode(Node $node)
            {
                // The second argument creates an alias; it does not require a pre-existing class.
                if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && strtolower($node->name->toString()) === 'class_alias') {
                    $alias = $node->args[1]->value ?? null;
                    if ($alias instanceof Node\Expr\ClassConstFetch && $alias->class instanceof Node\Name && $alias->name->toString() === 'class') {
                        $this->declarations[] = $alias->class->toString();
                    } elseif ($alias instanceof Node\Scalar\String_) {
                        $this->declarations[] = $alias->value;
                    }
                }

                return null;
            }

            public function enterNode(Node $node)
            {
                if ($node instanceof Node\Stmt\ClassLike && isset($node->namespacedName)) {
                    $this->declarations[] = $node->namespacedName->toString();
                }

                // Names in these positions resolve as namespaces, functions or constants, not classes.
                if ($node instanceof Node\Stmt\Namespace_ || $node instanceof Node\Stmt\Use_ || $node instanceof Node\Stmt\GroupUse || $node instanceof Node\UseItem) {
                    return null;
                }

                foreach ($node->getSubNodeNames() as $key) {
                    if ($key === 'name' && ($node instanceof Node\Expr\FuncCall || $node instanceof Node\Expr\ConstFetch)) {
                        continue;
                    }

                    $values = is_array($node->$key) ? $node->$key : [$node->$key];
                    foreach ($values as $value) {
                        if ($value instanceof Node\Name && !in_array(strtolower($value->toString()), ['self', 'static', 'parent'], true)) {
                            $this->references[] = ['name' => $value->toString(), 'line' => $value->getStartLine()];
                        }
                    }
                }

                return null;
            }
        };
        $traverser = new NodeTraverser(new NameResolver(), $collector);
        $traverser->traverse($nodes ?? []);

        return ['declarations' => $collector->declarations, 'references' => $collector->references];
    }
}
