<?php

namespace Acme\Example {
    class Bar{}
}

namespace {
    class Foo{
        public $bar;

        public function __construct()
        {
            $this->bar = new \Acme\Example\Bar();
        }
    }
}

namespace Tests
{
    require_once __DIR__ . '/../src/Polyfill.php';

    use Foo;
    use Acme\Example\Bar;

    class PolyfillUnserializeTest extends \PHPUnit_Framework_TestCase
    {
        public function testUnserializeWithoutOptionsReturnsObjects()
        {
            $foo = new Foo();
            $serialized = serialize($foo);

            $unserialized = \Polyfill\unserialize($serialized);

            $this->assertInstanceOf(Foo::class, $unserialized);
            $this->assertInstanceOf(Bar::class, $unserialized->bar);
        }

        public function testUnserializeWithAllowedClassesReturnsObjects()
        {
            $foo = new Foo();
            $serialized = serialize($foo);
            $options = [
                'allowed_classes' => [\Foo::class, Bar::class],
            ];
            $unserialized = \Polyfill\unserialize($serialized, $options);

            $this->assertInstanceOf(Foo::class, $unserialized);
            $this->assertInstanceOf(Bar::class, $unserialized->bar);
        }

        /**
         * @expectedException \PHPUnit_Framework_Error
         * @expectedExceptionMessage The script tried to execute a method or access a property of an incomplete object.
         */
        public function testUnserializeWithAllowedClassesFalseReturnsIncompleteObjects(
        )
        {
            $foo = new Foo();
            $serialized = serialize($foo);

            $unserialized = \Polyfill\unserialize(
                $serialized,
                ['allowed_classes' => null]
            );

            $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
            $unserialized->bar;
        }

        public function testUnserializeWithAllowedClassesNullIsSameAsFalse()
        {
            $foo = new Foo();
            $serialized = serialize($foo);

            $unserialized = \Polyfill\unserialize(
                $serialized,
                ['allowed_classes' => null]
            );

            $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized);
        }

        public function testUnserializeStringWithAllowedClassesFalse()
        {
            $string = 'This is an ordinary string';
            $serialized = serialize($string);

            $unserialized = \Polyfill\unserialize(
                $serialized,
                ['allowed_classes' => false]
            );

            $this->assertEquals($string, $unserialized);
        }

        public function testUnserializeArrayWithAllowedClassesFalse()
        {
            $array = [
                'key' => 42,
                1 => 'foo',
                'bar' => 'baz',
            ];
            $serialized = serialize($array);

            $unserialized = \Polyfill\unserialize(
                $serialized,
                ['allowed_classes' => false]
            );

            $this->assertSame($array, $unserialized);
        }

        public function testUnserializeWithEmbeddedClassNotBeingAllowed()
        {
            $foo = new Foo();
            $serialized = serialize($foo);
            $options = [
                'allowed_classes' => [Foo::class],
            ];

            $unserialized = \Polyfill\unserialize($serialized, $options);

            $this->assertInstanceOf(Foo::class, $unserialized);
            $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized->bar);
        }

        public function testUnserializeRecursiveClassBeingAllowed()
        {
            $foo = new Foo();
            $foo->baz = new Foo();
            $serialized = serialize($foo);
            $options = [
                'allowed_classes' => [Foo::class],
            ];

            $unserialized = \Polyfill\unserialize($serialized, $options);

            $this->assertInstanceOf(Foo::class, $unserialized);
            $this->assertInstanceOf(Foo::class, $unserialized->baz);
            $this->assertInstanceOf('__PHP_Incomplete_Class', $unserialized->baz->bar);
        }
    }
}