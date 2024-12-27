<?php
include 'db_connection.php';

// Manejar la búsqueda de productos
if (isset($_GET['buscar']) && $_GET['buscar'] !== '') {
    $buscar = $_GET['buscar'];
    $sql_buscar = "SELECT * FROM productos WHERE nombre LIKE :buscar";
    $stmt_buscar = $pdo->prepare($sql_buscar);
    $stmt_buscar->execute(['buscar' => "%$buscar%"]);
    $productos = $stmt_buscar->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Si no hay búsqueda, mostrar todos los productos
    $sql_todos = "SELECT * FROM productos";
    $stmt_todos = $pdo->query($sql_todos);
    $productos = $stmt_todos->fetchAll(PDO::FETCH_ASSOC);
}

// Manejar la edición de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nuevo_nombre = $_POST['nuevo_nombre'];
    $nuevo_precio = $_POST['nuevo_precio'];
    $nueva_cantidad = $_POST['nueva_cantidad'];

    $sql_editar = "UPDATE productos SET nombre = :nombre, precio = :precio, cantidad = :cantidad WHERE id = :id";
    $stmt_editar = $pdo->prepare($sql_editar);
    $stmt_editar->execute([
        'nombre' => $nuevo_nombre,
        'precio' => $nuevo_precio,
        'cantidad' => $nueva_cantidad,
        'id' => $id,
    ]);
    $mensaje = "Producto actualizado correctamente.";
}

// Manejar la eliminación de productos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $id = $_POST['id'];

    $sql_eliminar = "DELETE FROM productos WHERE id = :id";
    $stmt_eliminar = $pdo->prepare($sql_eliminar);
    $stmt_eliminar->execute(['id' => $id]);

    $mensaje = "Producto eliminado correctamente.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Lista de Productos</h2>

    <?php if (isset($mensaje)): ?>
        <div class="alert alert-info"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <!-- Buscar productos -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="buscar" class="form-control" placeholder="Buscar productos" value="<?php echo $_GET['buscar'] ?? ''; ?>">
            <button type="submit" class="btn btn-primary">Buscar</button>
            <a href="http://localhost/gestion_pedidos2.0/" class="btn btn-secondary">Regresar al Menú</a>
        </div>
    </form>

    <!-- Mostrar productos encontrados o todos los productos -->
    <?php if (!empty($productos)): ?>
        <h3><?php echo isset($_GET['buscar']) && $_GET['buscar'] !== '' ? 'Resultados de búsqueda:' : 'Lista completa de productos:'; ?></h3>
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?php echo $producto['nombre']; ?></td>
                    <td><?php echo $producto['precio']; ?></td>
                    <td><?php echo $producto['cantidad']; ?></td>
                    <td>
                        <!-- Formulario para editar producto -->
                        <form method="POST" class="mb-2">
                            <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                            <div class="mb-2">
                                <input type="text" name="nuevo_nombre" class="form-control" value="<?php echo $producto['nombre']; ?>" placeholder="Nuevo Nombre">
                            </div>
                            <div class="mb-2">
                                <input type="number" step="0.01" name="nuevo_precio" class="form-control" value="<?php echo $producto['precio']; ?>" placeholder="Nuevo Precio">
                            </div>
                            <div class="mb-2">
                                <input type="number" name="nueva_cantidad" class="form-control" value="<?php echo $producto['cantidad']; ?>" placeholder="Nueva Cantidad">
                            </div>
                            <button type="submit" name="editar" class="btn btn-success">Actualizar</button>
                        </form>

                        <!-- Formulario para eliminar producto -->
                        <form method="POST">
                            <input type="hidden" name="id" value="<?php echo $producto['id']; ?>">
                            <button type="submit" name="eliminar" class="btn btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No se encontraron productos.</p>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>



