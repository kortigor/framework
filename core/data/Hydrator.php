<?php

declare(strict_types=1);

namespace core\data;

use ReflectionClass;

/**
 * Create hydrated class instances.
 */
class Hydrator
{
    /**
     * @var array<string, ReflectionClass>
     */
    private array $reflectionClassMap;

    /**
     * Create and hydrate instance of the class.
     * 
     * @param string $class Class name
     * @param array<string, mixed> $data Hydration data in pairs $property => $value
     * 
     * @return object Hydrated object
     */
    public function hydrate(string $class, array $data): object
    {
        $reflection = $this->getReflectionClass($class);
        $target = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $name => $value) {
            if (!$reflection->hasProperty($name)) {
                continue;
            }

            $property = $reflection->getProperty($name);
            if ($property->isPrivate() || $property->isProtected()) {
                $property->setAccessible(true);
            }
            $property->setValue($target, $value);
        }

        return $target;
    }

    /**
     * Extract object properties data into array.
     * 
     * @param object $object Object to extract.
     * @param string[] $fields Properties names to extract.
     * 
     * @return array Extracted data in pairs $property => $value.
     */
    public function extract(object $object, array $fields): array
    {
        $result = [];
        $reflection = $this->getReflectionClass(get_class($object));
        foreach ($fields as $name) {
            if ($reflection->hasProperty($name)) {
                $property = $reflection->getProperty($name);
                if ($property->isPrivate() || $property->isProtected()) {
                    $property->setAccessible(true);
                }
                $result[$property->getName()] = $property->getValue($object);
            }
        }

        return $result;
    }

    /**
     * Get class reflection.
     * 
     * @param string $class
     * 
     * @return ReflectionClass
     */
    private function getReflectionClass(string $class): ReflectionClass
    {
        if (!isset($this->reflectionClassMap[$class])) {
            $this->reflectionClassMap[$class] = new ReflectionClass($class);
        }
        return $this->reflectionClassMap[$class];
    }
}