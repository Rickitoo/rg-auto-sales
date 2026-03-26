<?php
// admin/carro_add.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/../conexao.php";

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Novo Carro | Admin RG</title>
  <link rel="stylesheet" href="../style.css">
</head>
<body>
  <div class="small-container">
    <h2 class="title">Adicionar carro</h2>

    <form action="carro_save.php" method="POST" enctype="multipart/form-data" style="max-width:720px;">
      <label>Marca</label>
      <input type="text" name="marca" required>

      <label>Modelo</label>
      <input type="text" name="modelo" required>

      <label>Ano</label>
      <input type="number" name="ano" min="1900" max="2099" required>

      <label>Preço (MT)</label>
      <input type="number" name="preco" min="0" step="1" required>

      <label>Status</label>
      <select name="status" required>
        <option value="disponivel">disponivel</option>
        <option value="vendido">vendido</option>
        <option value="reservado">reservado</option>
      </select>

      <label>Descrição</label>
      <textarea name="descricao" rows="5"></textarea>

      <label>Foto principal (JPG/PNG/WEBP)</label>
      <input type="file" name="imagem_capa" accept=".jpg,.jpeg,.png,.webp" required>
      <label>Galeria (podes selecionar várias)</label>
      <input type="file" name="galeria[]" accept=".jpg,.jpeg,.png,.webp" multiple required>
      <small style="opacity:.8;">Dica: seleciona 6 a 12 fotos.</small>
      <a href="editar_carro.php?id=<?= $carro['id'] ?>" class="btn btn-primary">Editar</a>
      <button class="btn" type="submit">Guardar</button>
      <a class="btn btn--outline" href="../products.php">Ver site</a>
    </form>
  </div>
</body>
</html>