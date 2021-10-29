<?php

declare(strict_types=1);

namespace core\base;

/**
 * Singleton pattern implementatation abstract.
 */
abstract class Singleton
{
     /**
      * @var array Instances storage.
      */
     private static array $instances = [];

     /**
      * Disable instantiate with "new" operator.
      */
     protected function __construct()
     {
     }

     /**
      * Disable clone singleton.
      */
     protected function __clone()
     {
     }

     /**
      * Disable wakeup.
      */
     public function __wakeup()
     {
          throw new \Exception('Cannot unserialize a singleton.');
     }

     /**
      * Instantiate method.
      *
      * @return self
      */
     final public static function getInstance(): self
     {
          $subclass = static::class;
          if (!isset(self::$instances[$subclass])) {
               /* Use "static" instead class name.
               In this context "static" means "current class name".
               It's important, because need to create instance of class from which we call this method.
                */
               self::$instances[$subclass] = new static;
          }
          return self::$instances[$subclass];
     }
}