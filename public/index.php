<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once '../vendor/autoload.php';
require_once '../config.php';

$app = new \Slim\App(['settings' => $config]);

$container = $app->getContainer();

$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    $pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'], $db['user'], $db['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

$app->get('/hello', function (Request $request, Response $response) {

    $response->getBody()->write("Hello World");

    var_dump($this->db);

    return $response;
});

// List all products
//
$app->get('/products', function (Request $request, Response $response) {
    $sql= 'SELECT * FROM products ORDER BY id DESC;';
    $query = $this->db->query($sql);

    $products= $query->fetchAll(PDO::FETCH_OBJ);

    $result=array(
        'status'=> 'success',
        'code' => 200,
        'data' => $products
    );
    
    echo json_encode($result);
});

// Insert product
//
$app->post('/products', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    // $ticket_data = [];
    // $ticket_data['title'] = filter_var($data['title'], FILTER_SANITIZE_STRING);
    // $ticket_data['description'] = filter_var($data['description'], FILTER_SANITIZE_STRING);
   
    if(!isset($data['name'])){
        $data['name']= null;
    }

    if(!isset($data['description'])){
        $data['description']= null;
    }

    if(!isset($data['price'])){
        $data['price']= null;
    }

    if(!isset($data['image'])){
        $data['image']= null;
    }

    // var_dump($data);
    
    $row = [
        'name' => $data['name'],
        'description' => $data['description'],
        'price' => $data['price'],
        'image' => $data['image']
    ];
    

    $query = "INSERT INTO products SET name=:name, description=:description, price=:price, image=:image;";

    $success = $this->db->prepare($query)->execute($row);

    $result=array(
        'status'=> 'error',
        'code' => 404,
        'message' => 'The product could not be created'
    );

    if($success){
        $result=array(
            'status'=> 'success',
            'code' => 200,
            'message' => 'Successfully created product'
        );
    }

    // return $response->withJson(['success' => $success]);
    echo json_encode($result);

});

$app->run();

