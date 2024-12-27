<?php
include 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre = $_POST["nombre"];
    $cantidad = $_POST["cantidad"];
    $precio = $_POST["precio"];

    if (!empty($nombre) && $cantidad > 0 && $precio > 0) {
        $sql = "INSERT INTO productos (nombre, cantidad, precio) VALUES (:nombre, :cantidad, :precio)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(["nombre" => $nombre, "cantidad" => $cantidad, "precio" => $precio]);
        $mensaje = "Producto agregado correctamente.";
    } else {
        $mensaje = "Por favor, completa todos los campos correctamente.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Producto</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Agregar Producto</h2>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label for="nombre" class="form-label">Nombre del Producto</label>
            <input type="text" id="nombre" name="nombre" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="cantidad" class="form-label">Cantidad</label>
            <input type="number" id="cantidad" name="cantidad" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="precio" class="form-label">Precio</label>
            <input type="number" id="precio" name="precio" class="form-control" min="0.01" step="0.01" required>
        </div>
        <button type="submit" class="btn btn-primary">Agregar Producto</button>
        <a href="http://localhost/gestion_pedidos2.0/" class="btn btn-secondary">Regresar al Menú</a>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


