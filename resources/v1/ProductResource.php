<?php
require_once '../config/database.php';
require_once '../models/products.php';

class ProductResource
{
    private $db;
    private $product;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->product = new products($this->db);
    }

    // GET /api/v1/products
    public function index()
    {
        header("Content-Type: application/json");

        $stmt = $this->product->read();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $products_arr = array();
            $products_arr["records"] = array();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                $product_item = array(
                    "id" => $id,
                    "sku" => $sku,
                    "name" => $name,
                    "description" => html_entity_decode($description),
                    "price" => $price,
                    "stock" => $stock,
                    "created_at" => $created_at,
                    "updated_at" => $updated_at
                );
                array_push($products_arr["records"], $product_item);
            }

            http_response_code(200);
            echo json_encode($products_arr);
        } else {
            http_response_code(200);
            echo json_encode(array("records" => array()));
        }
    }   

    // GET /api/v1/products/{id}
    public function show($id)
    {
        header("Content-Type: application/json");

        $this->product->id = $id;

        if ($this->product->readOne()) {
            $product_arr = array(
                "id" => $this->product->id,
                "sku" => $this->product->sku,
                "name" => $this->product->name,
                "description" => html_entity_decode($this->product->description),
                "price" => $this->product->price,
                "stock" => $this->product->stock,
                "created_at" => $this->product->created_at,
                "updated_at" => $this->product->updated_at
            );

            http_response_code(200);
            echo json_encode($product_arr);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Producto no encontrado."));
        }
    }

    // POST /api/v1/products
    public function store()
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        if (
            !empty($data->sku) &&
            !empty($data->name) &&
            !empty($data->description) &&
            !empty($data->price) &&
            !empty($data->stock)
        ) {
            $this->product->sku = $data->sku;
            $this->product->name = $data->name;
            $this->product->description = $data->description;
            $this->product->price = $data->price;
            $this->product->stock = $data->stock;

            if ($this->product->create()) {
                http_response_code(201);
                echo json_encode(array("message" => "Producto creado."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo crear el producto."));
            }
         } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos."));
        }
    }

    // PUT /api/v1/products/{id}
    public function update($id)
    {
        header("Content-Type: application/json");

        $data = json_decode(file_get_contents("php://input"));

        $this->product->id = $id;

        if (
            !empty($data->sku) &&
            !empty($data->name) &&
            !empty($data->description) &&
            !empty($data->price) &&
            !empty($data->stock)
        ) {
            $this->product->sku = $data->sku;
            $this->product->name = $data->name;
            $this->product->description = $data->description;
            $this->product->price = $data->price;
            $this->product->stock = $data->stock;

            if ($this->product->update()) {
                http_response_code(200);
                echo json_encode(array("message" => "Producto actualizado."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "No se pudo actualizar el producto."));
            }
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "Datos incompletos."));
        }
    }

    // DELETE /api/v1/products/{id}
    public function destroy($id)
    {
        header("Content-Type: application/json");

        $this->product->id = $id;

        if ($this->product->delete()) {
            http_response_code(200);
            echo json_encode(array("message" => "Producto eliminado."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "No se pudo eliminar el producto."));
        }
    }
}