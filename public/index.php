<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;
use Slim\Http\UploadedFile;

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

// Headers config
//
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
header("Allow: GET, POST, OPTIONS, PUT, DELETE");
$method = $_SERVER['REQUEST_METHOD'];
if($method == "OPTIONS") {
    die();
}

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

// Fetch by id
//
$app->get('/product/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];

    $query="SELECT * FROM products WHERE id=?";
    
    $stmt = $this->db->prepare($query);
    $stmt->execute([$id]);
    $product=$stmt->fetch(PDO::FETCH_OBJ);

    $result=array(
        'status'=> 'error',
        'code' => 404,
        'message' => 'Product not found'
    );
    
    if($product) {

        $result=array(
            'status'=> 'success',
            'code' => 200,
            'data' => $product
        );
    }

    echo json_encode($result);
});


// Insert product
//
$app->post('/products', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    // $ticket_data = [];
    // $ticket_data['title'] = filter_var($data['title'], FILTER_SANITIZE_STRING);
    // $ticket_data['description'] = filter_var($data['description'], FILTER_SANITIZE_STRING);
    
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

// Delete product
//
$app->get('/delete-product/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];

    $query="DELETE FROM products WHERE id=?";

    $stmt = $this->db->prepare($query);
    $stmt->execute([$id]);

    $result=array(
        'status'=> 'error',
        'code' => 404,
        'message' => 'Error while deleting product'
    );

    if($stmt) {
        $result=array(
            'status'=> 'success',
            'code' => 200,
            'message' => 'Successfully deleted product'
        );
    }

    echo json_encode($result);
});


// Update product
//
$app->post('/update-product/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];

    $data = $request->getParsedBody();
    $row = [
        'id' => $id,
        'name' => $data['name'],
        'description' => $data['description'],
        'price' => $data['price']
    ];

    $query = "UPDATE products SET name=:name, description=:description, price=:price";

    if(isset($data['image'])) {
        $query.=", image=:image";
        $row['image']=$data['image'];
    }

    $query.=" WHERE id=:id;";

    $success = $this->db->prepare($query);
    $success->execute($row);

    $result=array(
        'status'=> 'error',
        'code' => 404,
        'message' => 'The product could not be updated'
    );

    if($success){
        $result=array(
            'status'=> 'success',
            'code' => 200,
            'message' => 'Successfully updated product'
        );
    }

    echo json_encode($result);
});

// Upload file
//
$container['upload_directory'] = '../uploads';

$app->post('/upload-file', function (Request $request, Response $response) {

    $result=array(
        'status'=> 'error',
        'code' => 404,
        'message' => 'The file could not be uploaded'
    );

    $directory = $this->get('upload_directory');

    $uploadedFiles = $request->getUploadedFiles();

    // handle single input with single file upload
    $uploadedFile = $uploadedFiles['uploads'];

    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
       $filename = moveUploadedFile($directory, $uploadedFile);
        //$response->write('uploaded ' . $filename . '<br/>');

        $result=array(
            'status'=> 'success',
            'code' => 200,
            'message' => 'Successfully uploaded file',
            'filename' => $filename
        );
    }

    echo json_encode($result);
});

function moveUploadedFile($directory, UploadedFile $uploadedFile)
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . '/' . $filename);

    return $filename;
}

$app->run();

