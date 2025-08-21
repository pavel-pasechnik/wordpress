<?php

namespace Advanced_Media_Offloader\Core;

class Container
{
    private array $services = [];
    private array $instances = [];

    public function register(string $id, $concrete): self
    {
        $this->services[$id] = $concrete;
        return $this;
    }

    public function get(string $id)
    {
        if (!isset($this->services[$id])) {
            throw new \Exception("Service '$id' not found in container");
        }

        // Return cached instance if available
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // Create new instance if service is a factory function
        if ($this->services[$id] instanceof \Closure) {
            $this->instances[$id] = $this->services[$id]($this);
        } else {
            $this->instances[$id] = $this->services[$id];
        }

        return $this->instances[$id];
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
