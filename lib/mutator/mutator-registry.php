<?php

class MutatorRegistry {
    /** @var callable[][][] */
    private array $mutators = [];

    public function add(callable $mutator, string $service, array $houses): void
    {
        foreach ($houses as $house) {
            $this->mutators[$service][$house][] = $mutator;
        }
    }

    public function apply(object &$object, string $service, string $house): void
    {
        foreach (($this->mutators[$service][""] ?? []) as $callback) {
            $callback($object);
        }

        foreach (($this->mutators[$service][$house] ?? []) as $callback) {
            $callback($object);
        }
    }
}
