arrch
=====

Library for PHP array queries, sorting, and more.

## Usage

The main Arrch method, `find()`, is most commonly used and is a combination of Arrch's `where()`, `sort()`, and limit/offset options. `where()` and `sort()` can be used on their own, as well.

### Data

We need some data to start with, preferably a large array of objects or multidimensional arrays. Here's a cross section of an example array...

	// this is a LARGE array of multidimensional associative arrays
	$data = array(
		…,
		4037 => array(
			'first_name'	=> 'Brian',
			'last_name'		=> 'Searchable',
			'age'		=> 27,
			'email'		=> 'bsearchable@example.com',
			'favorites'		=> array(
				'color'		=> 'blue',
				'number'		=> 4,
			),
		),
		…,
	);

### Example

We'll use Arrch statically to try and find Brian using a few conditions that return `true` for Brian's data.

	// this query would find our friend, Brian, plus
	// any other Brians over age 25
	$results = Arrch::find($data, array(

		'where' => array(
			// test for an exact match (===)
			array('first_name', 'Brian'),

			// use an operator
			array('age', '>', 25),
		),
	));

## Methods

### Arrch::find($data, array $options, $key = null)

	$options = array(
		'where' => array(
			array('value', 'search_value'),
			array('value', 'operator', 'search_value'),
		),
		'limit' 		=> 0,
		'offset' 		=> 0,
		'sort_key'		=> null,
		'sort_order'	=> 'ASC'
	)
