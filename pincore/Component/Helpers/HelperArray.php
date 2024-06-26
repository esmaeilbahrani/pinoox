<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Component\Helpers;

use Closure;
use Pinoox\Component\Package\App;
use Pinoox\Component\Validation;
use ReflectionException;

class HelperArray
{

    /**
     * Values search by a pattern of an array
     *
     * @var array
     */
    private static $resultArray = array();

    /**
     * Status required to search by a pattern of an array
     *
     * @var bool
     */
    private static $required = true;

    /**
     * Get count maximum depth of an array
     *
     * @param array $array
     * @param string|null $childrenKey
     * @return int
     */
    public static function depth($array, $childrenKey = null)
    {
        if (!is_null($childrenKey) && !empty($array[$childrenKey])) {
            $array = $array[$childrenKey];
        }

        $max_depth = 1;

        foreach ($array as $value) {
            if (is_array($value)) {
                $depth = self::depth($value, $childrenKey) + 1;

                if ($depth > $max_depth) {
                    $max_depth = $depth;
                }
            }
        }

        return $max_depth;
    }

    /**
     * Transformation an array to pattern ideal
     *
     * @param array $array
     * @param array $pattern
     * @param string|null $keyArray
     * @return array
     */
    public static function transformation($array, $pattern, $keyArray = null)
    {
        $result = [];
        foreach ($array as $key => $arr) {
            if (is_null($keyArray)) {
                if (isset($result[$key]))
                    $result[$key] = self::convertByPattern($arr, $pattern, $result[$key]);
                else
                    $result[$key] = self::convertByPattern($arr, $pattern);

            } else {
                if (isset($result[$arr[$keyArray]]))
                    $result[$arr[$keyArray]] = self::convertByPattern($arr, $pattern, $result[$arr[$keyArray]]);
                else
                    $result[$arr[$keyArray]] = self::convertByPattern($arr, $pattern);

            }
        }
        return $result;
    }

    /**
     * Convert pattern for an array transformation
     *
     * @param array $array
     * @param array $pattern
     * @param array $result
     * @return array
     */
    private static function convertByPattern($array, $pattern, $result = [])
    {
        foreach ($pattern as $key => $itemP) {
            $key = self::getValueTransformation($key, $array, $result);

            if (is_array($itemP)) {
                $result[$key][] = self::convertByPattern($array, $itemP);
            } else {

                $value = self::getValueTransformation($itemP, $array, $result, $key);
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Get value transformation
     *
     * @param Closure|string $value
     * @param array $array
     * @param array $result
     * @param string|null $key
     * @return bool|int|mixed|string|null
     */
    private static function getValueTransformation($value, $array, $result, $key = null)
    {
        if (is_callable($value)) {
            return $value($array, $result);
        }

        $is_sum = false;
        $is_variable = false;
        if (!empty($key) && Str::firstHas($value, '+')) {
            $value = Str::firstDelete($value, '+');
            $is_sum = true;
        }
        if (Str::firstHas($value, '$')) {
            $value = Str::firstDelete($value, '$');
            $is_variable = true;
        }

        if ($is_variable)
            $value = (isset($array[$value])) ? $array[$value] : null;

        if ($is_sum) {
            if (is_numeric($value)) $value = (isset($result[$key])) ? intval($result[$key]) + intval($value) : intval($value);
            else $value = (isset($result[$key])) ? $result[$key] . $value : $value;
        }

        return $value;
    }

    /**
     * Transform nested array to flat array
     *
     * @param array|string|mixed $input
     * @return array
     */
    public static function transformNestedArrayToFlatArray($input)
    {
        $output_array = [];
        if (is_array($input)) {
            foreach ($input as $value) {
                if (is_array($value)) {
                    $output_array = array_merge($output_array, self::transformNestedArrayToFlatArray($value));
                } else {
                    array_push($output_array, $value);
                }
            }
        } else {
            array_push($output_array, $input);
        }

        return $output_array;
    }

    /**
     * @param array|string|mixed $pattern
     * @param array $array
     * @param string $delimiter
     * @return array
     */
    public static function detachByPattern($pattern, $array, $delimiter = '.')
    {
        self::$resultArray = array();
        self::$required = true;
        self::getValuesByStar($pattern, $array, $delimiter);
        return ['values' => self::$resultArray, 'required' => self::$required];
    }

    /**
     * Get values by star in pattern
     *
     * @param array|string|mixed $pattern
     * @param array $array
     * @param string $delimiter
     */
    private static function getValuesByStar($pattern, $array, $delimiter = '.')
    {
        if (!is_array($pattern)) $pattern = Str::multiExplode($delimiter, $pattern);
        foreach ($array as $key => $value) {
            $p = $pattern;

            if (isset($p[0])) {
                $main = $p[0];
                array_shift($p);
            } else {
                continue;
            }

            $main = (is_numeric($main)) ? intval($main) : $main;
            $key = (is_numeric($key)) ? intval($key) : $key;


            if ($main === '*' || $main === $key) {
                if (empty($p)) {
                    self::$resultArray[] = $value;
                    if ($main !== '*') break;
                } else if (is_array($value)) {
                    self::getValuesByStar($p, $value);
                } else {
                    self::$required = false;
                }
            } else if (empty($p)) {
                if (!isset($array[$main]))
                    self::$required = false;
            }

        }
    }

    /**
     * Remove value by nested key
     *
     * @param array &$array
     * @param string $keys
     */
    public static function removeNestedKey(&$array, $keys)
    {
        if (empty($keys)) return;

        if (count($keys) == 1) {
            if (isset($array[$keys[0]])) {
                unset($array[$keys[0]]);
                return;
            }
        }
        foreach ($keys as $k) {
            if (isset($array[$k])) {
                array_shift($keys);
                self::removeNestedKey($array[$k], $keys);
            }
        }
    }

    /**
     * Exists value by nested key
     *
     * @param array $array
     * @param array $keys
     * @return bool
     */
    public static function existsNestedKey($array, $keys)
    {
        foreach ($keys as $key) {
            if (isset($array[$key]))
                $array = $array[$key];
            else
                return false;
        }
        return true;
    }

    /**
     * Get value by nested key
     *
     * @param array $array
     * @param array $keys
     * @return mixed|null
     */
    public static function getNestedKey($array, $keys)
    {
        foreach ($keys as $key) {
            if (isset($array[$key]))
                $array = $array[$key];
            else
                return null;
        }
        return $array;
    }

    /**
     * Convert an array to an array for javascript
     *
     * @param array $array
     * @return string
     */
    public static function convertToArrayJavascript($array)
    {
        $result = "[";
        $isFirst = true;
        foreach ($array as $item) {
            if (!$isFirst) $result .= ',';
            if (is_array($item)) {
                $item = self::convertToObjectJavascript($item);
            } else if (!is_numeric($item)) {
                $item = "'" . $item . "'";
            }

            $result .= $item;
            $isFirst = false;
        }
        $result .= "]";
        return $result;
    }

    /**
     * Convert an array to an object for javascript
     *
     * @param array $array
     * @return string
     */
    public static function convertToObjectJavascript($array)
    {
        $result = "{";
        $isFirst = true;
        foreach ($array as $key => $item) {
            if (!$isFirst) $result .= ',';
            if (is_array($item)) {
                $item = self::convertToObjectJavascript($item);
            } else if (!is_numeric($item)) {
                $item = "'" . $item . "'";
            }

            $key = (is_numeric($key)) ? $key : "'" . $key . "'";
            $result .= $key . ":" . $item;
            $isFirst = false;
        }
        $result .= "}";
        return $result;
    }

    /**
     * Parse params an array
     */
    public static function parseParams($array, $keys, $default = null, $removeNull = false)
    {
        $data = [];

        // Convert wildcard keys to all keys of the array
        if ($keys == '*') {
            $keys = (!empty($array) && is_array($array)) ? array_keys($array) : $array;
        }

        // Ensure keys is an array
        $keys = is_array($keys) ? $keys : explode(',', $keys);

        foreach ($keys as $key => $defaultValue) {
            // Extract key and default value if specified as key=value pair
            if (is_numeric($key)) {
                if (str_contains($defaultValue, '=')) {
                    [$key, $defaultValue] = explode('=', $defaultValue, 2);
                } else {
                    $key = $defaultValue;
                    $defaultValue = $default;
                }
            }

            // Remove HTML flag if present
            $isHtml = str_contains($key, '!');
            $key = str_replace('!', '', $key);

            // Skip null values if removeNull is true
            if ($removeNull && !isset($array[$key])) {
                continue;
            }

            // Handle array values
            $value = $array[$key] ?? $defaultValue;
            $data[$key] = is_array($value) ? $value : self::sanitizeValue($value);
        }

        // If there's only one item in $data, return that item instead of the array
        if (count($data) === 1) {
            return reset($data);
        }

        return $data;
    }

    private static function sanitizeValue($value)
    {
        if (is_string($value)) {
            $value = htmlspecialchars(stripslashes($value));
        }

        return $value;
    }


    /**
     * Parse one param an array
     */
    public static function parseParam($array, $key, $default = null)
    {
        $isHtml = Str::has($key, '!');
        $key = is_string($key) ? str_replace('!', '', $key) : $key;
        $value = $array[$key] ?? $default;
        return $isHtml || is_array($value) ? $value : (is_string($value) ? htmlspecialchars(stripslashes($value)) : $value);
    }

    /**
     * Get last key of array
     *
     * @param array $arr
     * @return int|string|null
     */
    public static function lastKey($arr)
    {
        end($arr);
        return key($arr);
    }

    /**
     * Flip array (support multi array)
     *
     * @param array $array
     * @param bool $isArray
     * @param bool $isMultiple
     * @return array|null
     */
    public static function flip($array, $isArray = true, $isMultiple = true)
    {
        if (!$isMultiple)
            return array_flip($array);

        $result = [];
        foreach ($array as $key => $value) {

            if (empty($value) || !(is_numeric($value) || is_string($value)))
                continue;

            if ($isArray || isset($result[$value])) {
                if (!is_array($result[$value]))
                    $result[$value] = [$result[$value]];

                $result[$value][] = $key;
            } else {
                $result[$value] = $key;
            }
        }

        return $result;
    }

    /**
     * Write data in config
     *
     * @param string $key
     * @param mixed $value
     * @param string $action
     * @param array $data
     */
    public static function pushingData(string $key, mixed $value, string $action, array &$data)
    {
        $temp = &$data;
        $parts = explode('.', $key);
        $countKeys = count($parts) - 1;
        $key = null;
        for ($i = 0; $i <= $countKeys; $i++) {
            $key = $parts[$i];
            if (($i != $countKeys)) {
                if (!isset($temp[$key]))
                    $temp[$key] = [];
                else if (!is_array($temp[$key]))
                    $temp[$key] = [$temp[$key]];

                $temp = &$temp[$key];
            }
        }


        if ($action == 'add') {
            if (!isset($temp[$key])) {
                $temp[$key] = [$value];
            } else {
                if (!is_array($temp[$key]))
                    $temp[$key] = [$temp[$key]];
                $temp[$key][] = $value;
            }
        } else if ($action == 'set') {
            $temp[$key] = $value;
        } else if ($action == 'del') {
            unset($temp[$key]);
        }
    }

    public static function setData()
    {

    }

    public static function pullingData(string $key, array $data): mixed
    {
        if (is_null($key)) return $data;

        $parts = explode('.', $key);
        if (is_array($data)) {
            foreach ($parts as $value) {
                if (isset($data[$value])) {
                    $data = $data[$value];
                } else {
                    $data = null;
                    break;
                }
            }
        } else {
            $data = null;
        }

        return $data;
    }

    public static function getNestedValue(array $array, string $key)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $nestedKey) {

            if (isset($value[$nestedKey])) {
                $value = $value[$nestedKey];
            } else {
                return null;
            }
        }

        return $value;
    }

}