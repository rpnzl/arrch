<?php

/**
 * Arrch
 * 
 * @copyright  2012
 * @author     Michael Giuliana
 * @link       http://rpnzl.com
 * @version    1.0
 * @since      2012/11
 * @license    MIT License - http://opensource.org/licenses/MIT
 * 
 * A small library of array search and sort methods. Perhaps most
 * useful paired with a simple, flat-file cache system, Arrch
 * allows psuedo-queries of large arrays.
 */
class Arrch
{
    /**
     * @var  arr  $_defaults  The find() method default values.
     */
    public static $_defaults = array(
        'where'         => array(),
        'limit'         => 0,
        'offset'        => 0,
        'sort_key'      => null,
        'sort_order'    => 'ASC'
    );

    /**
     * @var  str  $_val_split  The string to designate multiple potential values in conditions.
     */
    public static $_val_split = '|';

    /**
     * @var  str  $_key_split  The string to split keys when checking a deep multidimensional array value.
     */
    public static $_key_split = '.';

    /* ---------------------------------------------------------------------------------
     *
     * Utilities
     * 
     * -------------------------------------------------------------------------------*/

    /**
     * Magic method for instantiated usage.
     * 
     * @param   str  $name  The method name that was called.
     * @param   arr  $args  An array of arguments passed to the method.
     * @return  arr  The modified array of data.
     */
    public function __call($name, $args)
    {
        // Must pass data by reference
        array_unshift($args, &$this->_data);
        return Arrch::_methods($name, $args);
    }

    /**
     * Magic method for static usage.
     * 
     * @param   str  $name  The method name that was called.
     * @param   arr  $args  An array of arguments passed to the method.
     * @return  arr  The modified array of data.
     */
    public static function __callStatic($name, $args)
    {
        // Must pass data by reference
        $args[0] = &$args[0];
        return Arrch::_methods($name, $args);
    }

    /**
     * Maps function calls to the appropriate Arrch method.
     * 
     * @param   str  $name  The method name that was called.
     * @param   arr  $args  An array of arguments passed to the method.
     * @return  arr  The modified array of data.
     */
    private static function _methods($name, $args)
    {
        if(method_exists('Arrch', '_'.$name))
        {
            return call_user_func_array(array('Arrch', '_'.$name), $args);
        }
        else
        {
            throw new Exception('Arrch doesn\'t contain that method!', 1);
        }
    }

    /* ---------------------------------------------------------------------------------
     *
     * Instantiated Usage
     * 
     * -------------------------------------------------------------------------------*/

    /**
     * @var  arr  $_data  The data we're searching.
     */
    public $_data;

    /**
     * Set the data upon instantiation.
     * 
     * @param   arr  $data  The array of data to search and sort.
     * @return  void
     */
    public function __construct(array $data)
    {
        $this->_data = $data;
    }

    /**
     * Update the data array.
     * 
     * @param   arr  $data  The array of data to search.
     */
    public function set_data(array $data)
    {
        if(isset($data))
        {
            $this->_data = $data;
        }

        return $this;
    }

    /* ---------------------------------------------------------------------------------
     *
     * Static Usage
     * 
     * -------------------------------------------------------------------------------*/

    /**
     * Find
     * 
     * This method combines the functionality of the
     * where() and sort() methods, with additional
     * limit and offset parameters. Returns an array of matching
     * array items. Will only sort if a sort key is set.
     * 
     * @param   arr    &$data     The array of objects or associative arrays to search.
     * @param   arr    $options   The query options, see Arrch::$_defaults.
     * @param   misc   $key       An item's key or index value.
     * @return  arr    The result array.
     */
    public static function _find(array &$data, array $options = array(), $key = null)
    {
        /**
         * Parameters
         */
        $options = array_merge(Arrch::$_defaults, $options);

        /**
         * Find One
         */
        if($key !== null)
        {
            $data = isset($data[$key]) ? $data[$key] : null;
            return $data;
        }

        /**
         * Find Many
         */
        else
        {
            // Where
            Arrch::_where($data, $options['where']);

            // Sort
            if( ! empty($options['sort_key']))
            {
                Arrch::_sort($data, $options['sort_key'], $options['sort_order']);
            }

            // Limit
            $data = array_slice($data, $options['offset'], ($options['limit'] == 0) ? null : $options['limit']);

            return $data;
        }
    }

    /**
     * Sort
     * 
     * Sorts an array of objects by the specified key.
     * 
     * @param   arr   &$data   The array of objects or associative arrays to sort.
     * @param   str   $key     The object key or key path to use in sort evaluation.
     * @param   str   $order   ASC or DESC.
     * @return  arr   The result array.
     */
    public static function _sort(array &$data, $key, $order = null)
    {
        // Default sort order
        $order = $order ?: Arrch::$_defaults['sort_order'];

        // Sort by key, maintain indexes
        uasort($data, function($a, $b) use ($key)
        {
            // Extract values
            $a_val = Arrch::extract_value($a, $key);
            $b_val = Arrch::extract_value($b, $key);

            // Strings
            if(is_string($a_val) && is_string($b_val))
            {
                return strcasecmp($a_val, $b_val);
            }
            // Ints & Floats
            elseif(is_int($a_val) && is_int($b_val) || is_float($a_val) && is_float($b_val))
            {
                if($a_val == $b_val)
                {
                    return 0;
                }
                else
                {
                    return ($a_val > $b_val) ? 1 : -1;
                }
            }
            // Bools
            elseif(is_bool($a_val) && is_bool($b_val))
            {
                if($a_val == $b_val)
                {
                    return 0;
                }
                else
                {
                    return ($a_val == true) ? 1 : -1;
                }
            }
        });

        // Descending order
        if(strtoupper($order) == 'DESC')
        {
            $data = array_reverse($data);
        }

        return $data;
    }

    /**
     * Where
     * 
     * Evaluates an object based on an array of conditions
     * passed into the function, formatted like so...
     * 
     *      array( 'name', 'arlington' );
     *      array( 'name', '!=', 'arlington' );
     * 
     * @param   arr   &$data        The array of objects or associative arrays to search.
     * @param   arr   $conditions   The conditions to evaluate against.
     * @return  arr   The result array.
     */
    public static function _where(array &$data, array $conditions = array())
    {
        // Loop array of conditions
        foreach($conditions as $condition)
        {
            /**
             * Condition is an array:
             *      array( 'key', 'value' )
             *      array( 'key', 'operator', 'value' )
             */
            if(is_array($condition))
            {
                array_walk($data, function(&$item, $key, $condition) use (&$data) {

                    // Does the property exist?
                    if(count($condition) <= 3)
                    {
                        // only one value?
                        $operator = isset($condition[2]) ? $condition[1] : '===';
                        $search_value = isset($condition[2]) ? $condition[2] : $condition[1];
                        $value = Arrch::extract_value($item, $condition[0]);

                        // The test value must equal something
                        if(!empty($search_value))
                        {
                            // check if multiple values exist
                            if(strstr($search_value, Arrch::$_val_split))
                            {
                                // Split values
                                $values = explode(Arrch::$_val_split, $search_value);

                                // Loop and add matches
                                $matches = 0;
                                foreach($values as $search_value)
                                {
                                    $compare = array($value, $operator, $search_value);
                                    if(Arrch::compare($compare))
                                    {
                                        $matches++;
                                    }
                                }

                                // No matches? Delete.
                                if($matches < 1)
                                {
                                    unset($data[$key]);
                                }
                            }

                            // Single value
                            else
                            {
                                $compare = array($value, $operator, $search_value);
                                if(!Arrch::compare($compare))
                                {
                                    unset($data[$key]);
                                }
                            }
                        }
                        else
                        {
                            unset($data[$key]);
                        }
                    }
                    else
                    {
                        // OR
                        if(in_array('or', $condition))
                        {
                            $matches = 0;
                            $search_value = array_pop($condition);
                            $operator = array_pop($condition);
                            foreach($condition as $or)
                            {
                                if($or !== 'or')
                                {
                                    // Get the or value
                                    $value = Arrch::extract_value($item, $or);

                                    // Is the value empty?
                                    if(!empty($value))
                                    {
                                        $compare = array($value, $operator, $search_value);
                                        if(Arrch::compare($compare))
                                        {
                                            ++$matches;
                                        }
                                    }
                                }
                            }

                            // All the ORs failed
                            if($matches === 0)
                            {
                                unset($data[$key]);
                            }
                        }
                    }

                }, $condition);
            }
        }

        return $data;
    }


    /**
     * Extract Value
     * 
     * Finds the requested value in a multidimensional
     * array or an object and returns it.
     * 
     * @param    mixed   $item   The item containing the value.
     * @param    str     $key    The key or key path to the desired value.
     * @return   mixed   The found value or null.
     */
    public static function extract_value($item, $key)
    {
        $item = is_object($item) ? (array) $item : $item;

        if(strstr($key, Arrch::$_key_split))
        {
            $keys = explode(Arrch::$_key_split, $key);
            foreach($keys as $key)
            {
                if(is_array($item))
                {
                    $item = $item[$key];
                }
            }

            $item = isset($item) ? $item : null;
        }
        else
        {
            $item = $item[$key];
        }

        return $item;
    }

    /**
     * Compare
     * 
     * Runs comparison operations on an array of values
     * formatted like so...
     * 
     *      e.g. array( $value, $operator, $search_value )
     * 
     * Returns true or false depending on the outcome of the
     * requested comparative operation (<, >, =, etc..).
     * 
     * @param   arr   $array  The comparison array.
     * @return  bool  Whether the operation evaluates to true or false.
     */
    public static function compare(array $array)
    {
        // Switches
        switch(count($array))
        {
            case 2:
                if($array[0] !== $array[1])
                    return false;
                break;

            case 3:
                switch( $array[1] )
                {
                    case('!='):
                        if($array[0] == $array[2])
                            return false;
                        break;

                    case('!=='):
                        if($array[0] === $array[2])
                            return false;
                        break;

                    case('=='):
                        if($array[0] != $array[2])
                            return false;
                        break;

                    case('==='):
                        if($array[0] !== $array[2])
                            return false;
                        break;

                    case('<'):
                        if($array[0] >= $array[2])
                            return false;
                        break;

                    case('>'):
                        if($array[0] <= $array[2])
                            return false;
                        break;

                    case('<='):
                        if($array[0] > $array[2])
                            return false;
                        break;

                    case('>='):
                        if($array[0] < $array[2])
                            return false;
                        break;

                    case('~'):
                        // If the variables don't immediately match
                        if($array[0] !== $array[2])
                        {
                            // strings
                            if(is_string($array[0]) && is_string($array[2]))
                            {
                                if(!stristr($array[0], $array[2]))
                                {
                                    return false;
                                }
                            }
                            // arrays
                            elseif(is_array($array[0]) && is_array($array[2]))
                            {}
                            // objects
                            elseif(is_object($array[0]) && is_object($array[2]))
                            {}
                            // types
                            elseif(gettype($array[0]) !== gettype($array[2]))
                            {
                                return false;
                            }
                            // catch all
                            else
                            {
                                return false;
                            }
                        }
                        break;
                }
                break;

            default:
                break;
        }

        return true;
    }
}