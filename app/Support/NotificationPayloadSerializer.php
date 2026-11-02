<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

/**
 * Generic capture/rebuild of a notification's constructor arguments, so any
 * notification can be logged and later resent without per-class plumbing.
 * Relies on this app's convention of constructor property promotion where
 * the property name matches the constructor parameter name.
 */
class NotificationPayloadSerializer
{
    /**
     * Capture a notification's constructor args as a JSON-safe array.
     * Eloquent model arguments become a class+id reference.
     */
    public static function capture(object $notification): array
    {
        $ref = new ReflectionClass($notification);
        $payload = [];

        foreach ($ref->getConstructor()?->getParameters() ?? [] as $param) {
            if (!$ref->hasProperty($param->getName())) {
                continue;
            }

            $prop = $ref->getProperty($param->getName());
            $prop->setAccessible(true);
            $value = $prop->getValue($notification);

            $payload[$param->getName()] = $value instanceof Model
                ? ['__model' => get_class($value), '__id' => $value->getKey()]
                : $value;
        }

        return $payload;
    }

    /**
     * Rebuild a notification instance from a captured payload.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException if a referenced model no longer exists.
     */
    public static function reconstruct(string $class, array $payload): object
    {
        $ref  = new ReflectionClass($class);
        $args = [];

        foreach ($ref->getConstructor()?->getParameters() ?? [] as $param) {
            $value = $payload[$param->getName()] ?? null;

            if (is_array($value) && array_key_exists('__model', $value)) {
                $value = $value['__model']::findOrFail($value['__id']);
            }

            $args[] = $value;
        }

        return $ref->newInstanceArgs($args);
    }
}
