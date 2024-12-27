<?php
include 'db_connection.php';

if (isset($_GET['query'])) {
    $query = $_GET['query'];

    $sql = "SELECT nombre FROM productos WHERE nombre LIKE :query LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['query' => "%$query%"]);
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($productos);
}
?>
