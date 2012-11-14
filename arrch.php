<?php

/**
 * Arrch
 * 
 * A small library of array search and sort methods. Perhaps most
 * useful paired with a simple, flat-file cache system, Arrch
 * allows psuedo-queries of large arrays. Here's an example...
 * 
 * 		// A cross section of a LARGE array
 * 		$data = array(
 * 			...,
 * 			array(
 * 				'id' 			=> 1403,
 * 				'first_name'	=> 'John',
 * 				'last_name'		=> 'Searchable',
 * 				'deceased'		=> false,
 *				'data'			=> array(
 * 					'children'	=> 3,
 * 					'dogs'		=> 10,
 * 					'rabbits'	=> 14
 * 				),
 * 			),
 * 			...,
 * 		);
 * 
 * 		// Query the data
 * 		$results = Arrch::find($data, array(
 * 			array('data.children', 3),
 * 			array('first_name', 'John')
 * 		), 0, 'last_name');
 * 
 * @version  1.0
 * @author   Michael Giuliana - rpnzl.com
 */
class Arrch
{
	/**
	 * Find
	 * 
	 * This method combines the functionality of the
	 * where() and sort() methods, with an additional
	 * limit parameter. Returns an array of matching
	 * array items. Will only sort if a sort key is set.
	 * 
	 * 		e.g. Arrch::find($data, array(array('id', 32)), 0, 'id', 'ASC');
	 * 
	 * @param   arr   &$data        The array of objects or associative arrays to search.
	 * @param   arr   $conditions  	The conditions to evaluate against.
	 * @param   int   $limit  		The number of objects to return, setting to 0 will return all.
	 * @param   str   $sort_key  	The array key or object property to sort by, levels separated by periods... data.somekeyindataarray
	 * @param   str   $sort_order  	Sort the results in asc or descending order.
	 * @return  void
	 */
	public static function find(array &$data, array $conditions = array(), $limit = 0, $sort_key, $sort_order = 'ASC')
	{
		// Where
		Arrch::where($data, $conditions);

		// Sort
		if(! empty($sort_key))
		{
			Arrch::sort($data, $sort_key, $sort_order);
		}

		// Limit
		if($limit != 0)
		{
			$data = array_slice($data, 0, $limit);
		}
	}

	/**
	 * Sort
	 * 
	 * Sorts an array of objects by the specified key.
	 * 
	 * @param   arr   &$data   The array of objects or associative arrays to sort.
	 * @param   str   $key     The object key to use in sort evaluation.
	 * @param   str   $order   ASC or DESC.
	 * @return  void
	 */
	public static function sort(array &$data, $key, $order = 'ASC')
	{
		// Sort by key
		usort($data, function($a, $b) use ($key)
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
	}

	/**
	 * Where
	 * 
	 * Evaluates an object based on an array of conditions
	 * passed into the function, formatted like so...
	 * 
	 * 		array( 'name', 'arlington' );
	 * 		array( 'name', '!=', 'arlington' );
	 * 
	 * @param   arr   &$data        The array of objects or associative arrays to search.
	 * @param   arr   $conditions   The conditions to evaluate against.
	 * @return  void
	 */
	public static function where(array &$data, array $conditions = array())
	{
		// Begin with an array of data
		$array_flag = true;

		// Do we have an array?
		if( ! is_array( $data ) )
		{
			$array_flag = false;
			$data = array( $data );
		}

		// Loop array of conditions
		foreach( $conditions as $condition )
		{
			/**
			 * Condition is an array:
			 * 		array( 'key', 'value' )
			 * 		array( 'key', 'operator', 'value' )
			 */
			if( is_array( $condition ) )
			{
				array_walk( &$data, function( &$item, $key, $condition ) {

					// Does the property exist?
					if( count( $condition ) <= 3 )
					{
						// only one value?
						$operator = ( isset( $condition[2] ) ) ? $condition[1] : '===';
						$search_value = ( isset( $condition[2] ) ) ? $condition[2] : $condition[1];
						$value = Arrch::extract_value( $item, $condition[0] );

						// The test value must equal something
						if( ! empty( $search_value ) )
						{
							// check if multiple values exist
							if( strstr( $search_value, '|') )
							{
								// Split values
								$values = explode( '|', $search_value );

								// Loop and add matches
								$matches = 0;
								foreach( $values as $search_value )
								{
									// Create comparison array
									$compare = array( $value, $operator, $search_value );

									// If values don't match
									if( Arrch::compare( $compare ) )
									{
										$matches++;
									}
								}

								// No matches? Delete.
								if( $matches < 1 )
								{
									$item = null;
								}
							}

							// Single value
							else
							{
								// Create comparison array
								$compare = array( $value, $operator, $search_value );

								// If values don't match
								if( ! Arrch::compare( $compare ) )
								{
									$item = null;
								}
							}
						}
						else
						{
							$item = null;
						}
					}
					else
					{
						// OR
						if( in_array( 'or', $condition ) )
						{
							$matches = 0;
							$search_value = array_pop( $condition );
							$operator = array_pop( $condition );
							foreach( $condition as $ors )
							{
								if( $ors !== 'or' )
								{
									// Get the or value
									$value = Arrch::extract_value( $item, $ors );

									// Is the value empty?
									if( ! empty( $value ) )
									{
										// Create comparison array
										$compare = array( $value, $operator, $search_value );

										// Increment matches
										if( Arrch::compare( $compare ) )
										{
											++$matches;
										}
									}
								}
							}

							// All the ORs failed
							if( $matches === 0 )
							{
								$item = null;
							}
						}
					}

				}, $condition );
			}
		}

		$data = array_values( array_filter( $data ) );
	}


	/**
	 * Extract Value
	 * 
	 * Finds the requested value in a multidimensional
	 * array and returns it.
	 * 
	 * @param   arr   $array  The comparison array.
	 * @return  bool  Whether the operation evaluates to true or false.
	 */
	public static function extract_value( $value, $key, $split = '.' )
	{
		if( strstr( $key, $split ) )
		{
			$keys = explode( $split, $key );
			foreach( $keys as $key )
			{
				if( is_object( $value ) )
				{
					$value = $value->{ $key };
				}
				elseif( is_array( $value ) )
				{
					$value = $value[ $key ];
				}
			}

			$value = isset( $value ) ? $value : null;
		}
		else
		{
			if( is_object( $value ) )
			{
				$value = $value->{ $key };
			}
			elseif( is_array( $value ) )
			{
				$value = $value[ $key ];
			}
		}

		return $value;
	}

	/**
	 * Compare
	 * 
	 * Runs comparison operations on an array of values
	 * formatted like so...
	 * 
	 * 		e.g. array( $value, $operator, $search_value )
	 * 
	 * Returns true or false depending on the outcome of the
	 * requested comparative operation (<, >, =, etc..).
	 * 
	 * @param   arr   $array  The comparison array.
	 * @return  bool  Whether the operation evaluates to true or false.
	 */
	public static function compare( array $array )
	{
		// Switches
		switch( count( $array ) )
		{
			case 2:
				if( $array[0] !== $array[1] )
					return false;
				break;

			case 3:
				switch( $array[1] )
				{
					case( '!=' ):
						if( $array[0] == $array[2] )
							return false;
						break;

					case( '!==' ):
						if( $array[0] === $array[2] )
							return false;
						break;

					case( '==' ):
						if( $array[0] != $array[2] )
							return false;
						break;

					case( '===' ):
						if( $array[0] !== $array[2] )
							return false;
						break;

					case( '<' ):
						if( $array[0] >= $array[2] )
							return false;
						break;

					case( '>' ):
						if( $array[0] <= $array[2] )
							return false;
						break;

					case( '<=' ):
						if( $array[0] > $array[2] )
							return false;
						break;

					case( '>=' ):
						if( $array[0] < $array[2] )
							return false;
						break;

					case( '~' ):
						// If the variables don't immediately match
						if( $array[0] !== $array[2] )
						{
							// strings
							if( is_string( $array[0] ) && is_string( $array[2] ) )
							{
								if( ! stristr( $array[0], $array[2] ) )
								{
									return false;
								}
							}
							// arrays
							elseif( is_array( $array[0] ) && is_array( $array[2] ) )
							{}
							// objects
							elseif( is_object( $array[0] ) && is_object( $array[2] ) )
							{}
							// types
							elseif( gettype( $array[0] ) !== gettype( $array[2] ) )
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