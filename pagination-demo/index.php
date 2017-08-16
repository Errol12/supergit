<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
<link rel="stylesheet" type="text/css" href="style.css">
<?php

require ('paginator.php');

$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'gitdb1';


$mysqli = new mysqli($host,$user,$pass,$db); 

//DO NOT limit this query with LIMIT keyword, or...things will break!
//$query = "SELECT * FROM movies";
$query = "SELECT * from PaaS";

//these variables are passed via URL
$limit = ( isset( $_GET['limit'] ) ) ? $_GET['limit'] : 3; //movies per page
$page = ( isset( $_GET['page'] ) ) ? $_GET['page'] : 1; //starting page
$links = 2;

$paginator = new Paginator( $mysqli, $query ); //__constructor is called
$results = $paginator->getData( $limit, $page );

//print_r($results);die; $results is an object, $result->data is an array

//print_r($results->data);die; //array

echo $paginator->createLinks( $links, 'pagination pagination-sm' );
//print_r($results->data);	
?>
<?php

for ($p = 0; $p < count($results->data); $p++): ?>

<?php 
        //store in $movie variable for easier reading
        $paas = $results->data[$p]; 
        ?>


        <?=$paas['name']?>

<?php endfor; ?>