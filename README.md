## arrch v1.1

A PHP library for array queries, sorting, and more.

### Usage

The main Arrch method, `find()`, is most commonly used and is a combination of Arrch's `where()`, `sort()`, and limit/offset options. `where()` and `sort()` can be used on their own, if needed.

#### Data

We need some data to start with, preferably a large array of objects or multidimensional arrays. Here's a cross section of an example array...

    // this is a LARGE array of multidimensional associative arrays
    $data = array(
        …,
        4037 => array(
            'name'  => array(
                'first' => 'Brian',
                'last'  => 'Searchable'
            ),
            'null'  => null,
            'age'   => 27,
            'email' => 'bsearchable@example.com',
            'likes' => array(
                'color' => 'blue',
                'food'  => 'cheese',
                'sauce' => 'duck'
            ),
        ),
        …,
    );

#### Quick Example

We'll use Arrch to try and find Brian using a few conditions that return `true` for Brian's data. You can traverse the multidimensional arrays in your query by using dot (.) notation, and use a standard collection of operators (which are listed below) to determine a match.

    // this query would find our friend, Brian, plus
    // any other Brians over age 25
    $results = Arrch::find($data, array(

        'where' => array(
            // tests for an exact match (===)
            array('name.first', 'Brian'),

            // use an operator
            array('age', '>', 25),
        ),
    ));

#### Conditions

Arrch conditions are pretty flexible, and can be thought of like MySQL's `AND`, `OR`, `LIKE`, and `IN` operators. First, a list of valid operators.

    // valid conditional operators
    array('==', '===', '!=', '!==', '>', '<', '>=', '<=', '~');

Second, examples of valid conditions.

    'where' => array(

        // OR statement
        array(array('name.first', likes.color), 'blue'),

        // AND statement, including another condition
        array('age', '>', 25),

        // IN statement
        array('likes.food', array('toast', 'foie gras', 'cheese')),

        // OR/IN combined
        array(array('email', 'age'), array(27, 'goober@goob.co')),

        // Yes, you can compare to NULL
        array('null', null)

    )

You may be wondering about the tilde (~) operator. That one is comparable to MySQL's LIKE statement. You can use it to check for similarity in strings, numbers, and even determine if an associative key exists.

    'where' => array(

        // First names that contain 'br'
        array('name.first', '~', 'br'),

        // Find folks that have a favorite food defined
        array('likes', '~', 'food')

    )

