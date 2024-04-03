<?php
require_once ("db.conn.php");
class Product
{
    private $_pdo;

    public function __construct()
    {
        global $pdo;
        $this->_pdo = $pdo;
    }

    public function readAll()
    {
        $stmt = $this->_pdo->query("SELECT *, json_agg(flavor_notes) AS flavor_notes_json FROM products GROUP BY id, title, category_id, category_title, description, price, stock_quantity, origin, roast_level,created_at,updated_at ORDER BY id");
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as &$row) {
            $row['flavor_notes'] = json_decode($row['flavor_notes_json'], true);
            $row['flavor_notes'] = $row['flavor_notes'][0];
            unset($row['flavor_notes_json']);
        }
        return $results;
    }

    public function read($id)
    {
        $sql = "SELECT *, json_agg(flavor_notes) AS flavor_notes_json FROM products WHERE id = ? GROUP BY id, title, category_id, category_title, description, price, stock_quantity, origin, roast_level,created_at,updated_at";
        $stmt = $this->_pdo->prepare($sql);
        $stmt->execute([$id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $result['flavor_notes'] = json_decode($result['flavor_notes_json'], true);
        $result['flavor_notes'] = $result['flavor_notes'][0];
        unset($result['flavor_notes_json']);
        return $result;
    }


}
?>