arrch
=====

Library for PHP array queries, sorting, and more.

## Usage

The main Arrch method, `find()`, is most commonly used and is a combination of Arrch's `where()`, `sort()`, and limit/offset options. `where()` and `sort()` can be used on their own, as well.

### Data

	// this is a LARGE array of multidimensional associative arrays
	$data = array(
		…,
		4037 => array(
			'first_name'	=> 'Brian',
			'last_name'		=> 'Searchable',
			'age'			=> 27,
			'email'			=> 'bsearchable@example.com',
			'favorites'		=> array(
				'color'			=> 'blue',
				'number'		=> 4,
			),
		),
		…,
	);

### Query

	// this query would find our friend, Brian
	Arrch::find($data, array(
		
		// test for an exact match (===)
		array('first_name', 'Brian'),
		
		// use an operator
		array('age', '>', 25),
	));

