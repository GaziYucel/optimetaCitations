<?php
/**
 * @file classes/Helpers/ClassHelper.php
 *
 * @copyright (c) 2021+ TIB Hannover
 * @copyright (c) 2021+ Gazi Yücel
 * @license Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ClassHelper
 * @brief Provides static helper methods for working with class instances.
 */

namespace APP\plugins\generic\citationManager\classes\Helpers;

use ReflectionClass;
use ReflectionProperty;

class ClassHelper
{
    /**
     * Get public properties of an object and assign null values.
     *
     * @param object $class The object for which properties are retrieved and assigned null values.
     * @return array An associative array with property names as keys and null values.
     */
    public static function getClassAsArrayNullAssigned(object $class): array
    {
        return self::getClassAsArrayWithValuesAssigned($class);
    }

    /**
     * Get public properties of an object and assign provided values.
     *
     * @param object $class  The object for which properties are retrieved and values assigned.
     * @param ?array  $values An associative array with property names as keys and corresponding values.
     * @return array An associative array with property names as keys and assigned values.
     */
    public static function getClassAsArrayWithValuesAssigned(object $class, ?array $values = null): array
    {
        $objVars = [];

        $reflect = new ReflectionClass($class);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if (!empty($values[$property->getName()])) {
                $objVars[$property->getName()] = $values[$property->getName()];
            } else {
                $objVars[$property->getName()] = null;
            }
        }

        return $objVars;
    }

    /**
     * Get a class instance with public properties assigned values.
     *
     * @param object $class  The class instance to be populated.
     * @param array|null  $values An associative array with property names as keys and corresponding values.
     * @return object An instance of the class with properties assigned values.
     */
    public static function getClassWithValuesAssigned(object $class, ?array $values): object
    {
        if(empty($values)) return new $class();

        $object = new $class();

        $reflect = new ReflectionClass($class);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            if (!empty($values[$property->getName()])) {
                $object->{$property->getName()} = $values[$property->getName()];
            }
        }

        return $object;
    }

    /**
     * Get public properties of an object as an array
     *
     * @param object $class The object for which properties are retrieved.
     * @return array A with property names as keys.
     */
    public static function getClassPropertiesAsArray(object $class): array
    {
        $objVars = [];

        $reflect = new ReflectionClass($class);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
                $objVars[] = $property->getName();
        }

        return $objVars;
    }

    /**
     * Get public constants of an object as an array
     *
     * @param object $class The object for which properties are retrieved.
     * @return array Array with constant names as keys. [ 'constant1' => 'value1', ... ]
     */
    public static function getClassConstantsAndValuesAsArray(object $class): array
    {
        $reflect = new ReflectionClass($class);
        return $reflect->getConstants(ReflectionProperty::IS_PUBLIC);
    }

    /**
     * Get class public properties as a csv, e.g. "id","title","pub_date"
     *
     * @param object $class
     * @return string
     */
    public static function getClassPropertiesAsCsv(object $class): string
    {
        $result = '';

        $reflect = new ReflectionClass($class);
        $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);

        foreach ($properties as $property) {
            $result .= '"' . $property->getName() . '",';
        }

        return trim($result, ',');
    }
}
