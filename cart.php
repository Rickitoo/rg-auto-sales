<?php
session_start();
include("includes/db.php");

$ids = $_SESSION['interesse'];

if (count($ids) > 0) {
    $ids_str = implode(",", $ids);
    $sql = "SELECT * FROM carros WHERE id IN ($ids_str)";
    $res = mysqli_query($conn, $sql);
}

if (!isset($_SESSION['interesse'])) {
    $_SESSION['interesse'] = [];
}

// adicionar carro
if (isset($_GET['add'])) {
    $id = (int)$_GET['add'];
    $_SESSION['interesse'][] = $id;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="ImagensRG/logo.png">
    <title>Cart-RG AUTO </title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
    
    <div class="container">
        <div class="navbar">
        <div class="logo">
           <a href="index.php"> <img src="ImagensRG/logo.png" width="100px"></a>
        </div>
        <nav>
            <!-- Menu -->
             <ul id="MenuItems">  
                <li><a href="index.php">Início</a></li>
                <li><a href="products.php">Carros</a></li>
                <li><a href="about.php">Sobre</a></li>
                <li><a href="contacto.php">Contacto</a></li>
                <li><a href="account.php">Conta</a></li>
                <li><a href="test_drive.php">Test Drive</a></li>
                <li><a href="leasing.php">Leasing</a></li>
                <li><a href="vender_carro.php">Vender</a></li>
            </ul>
        </nav>
        <a href="cart.php"><img src="ImagensRG/png-transparent-computer-icons-shopping-cart-basket-shopping-cart-text-hand-share-icon.png" width="30px" height="30px"></a>
        <img src="ImagensRG/pngtree-hamburger-like-menu-black-glyph-ui-icon-icon-concept-settings-vector-png-image_47699556.jpg" class="menu-icon" onclick="menutoggle()">
    </div>
    </div>

    <!--------cart items details------------->

    <div class="small-container cart-page">
        <table>
            <?php while($c = mysqli_fetch_assoc($res)): ?>
            <tr>
                <td>
                    <div class="cart-info">
                        <img src="<?= $c['imagem'] ?>">
                        <p><?= $c['marca'].' '.$c['modelo'] ?></p>
                        <small><?= number_format($c['preco']) ?> MT</small>
                    </div>
                </td>

                <td>
                    <a target="_blank"
                    href="https://wa.me/258862934721?text=Tenho interesse no <?= urlencode($c['marca'].' '.$c['modelo']) ?>">
                    WhatsApp
                    </a>
                </td>

                <td>
                    <a href="leasing.php?carro_id=<?= $c['id'] ?>">Leasing</a>
                </td>
            </tr>
            <?php endwhile; ?>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
            <tr>
                <td>
                    <div class="cart-info">
                        <img src="ImagensRG/IMG_5009.jpeg">
                        <p>Audi Q5</p>
                        <small>price: $35.000</small>
                        <br>
                        <a href="cart.php?add=<?= $id ?>" class="btn btn--small">Guardar</a>
                        <a href="">Remover</a>
                    </div>
                </td>
                <td><input type="number" value="1"></td>
                 <td>$135.000</td>
            </tr>
             <tr>
                <td>
                    <div class="cart-info">
                        <img src="ImagensRG/IMG_5010.jpeg">
                        <p>Toyota Coaster 2024</p>
                        <small>price: $105.000</small>
                        <br>
                        <a href="cart.php?add=<?= $id ?>" class="btn btn--small">Guardar</a>
                        <a href="">Remover</a>
                    </div>
                </td>
                <td><input type="number" value="1"></td>
                 <td>$105.000</td>
            </tr>
             <tr>
                <td>
                    <div class="cart-info">
                        <img src="ImagensRG/IMG_5011.jpeg">
                        <p>Toyota Coaster 2011</p>
                        <small>price: $65.000</small>
                        <br>
                        <a href="cart.php?add=<?= $id ?>" class="btn btn--small">Guardar</a>
                        <a href="">Remover</a>
                    </div>
                </td>
                <td><input type="number" value="1"></td>
                 <td>$65.000</td>
            </tr>
        </table>

        <div class="total-price">
            <table>
                <div style="text-align:center; margin-top:40px;">
                    <h2>Interessado nestes carros?</h2>

                    <a class="btn"
                    href="https://wa.me/258862934721?text=Olá RG Auto Sales, quero falar sobre alguns carros que vi no site."
                    target="_blank">
                        Falar no WhatsApp
                    </a>
                </div>
            </table>
        </div>
    </div>

<!----------footer------->

<div class="footer">
    <div class="container">
        <div class="row">
            <div class="footer-col-1">
                <h3>Download our App</h3>
                <p>Download App for Android and IOS mobile Phone.</p>
                <div class="app-logo">
                    <img src="ImagensRG/AppStore.png" >
                    <img src="ImagensRG/pngtree-google-play-store-vector-png-image_9183318.png" >
                </div>
            </div>
             <div class="footer-col-2">
                <img src="ImagensRG/logo.png">
                <p>Our purpose is to sustainably make the pleasure and Benefits of Sports Acessible to the Many</p>
            </div>
            <div class="footer-col-1">
                <h3>Useful links</h3>
                <ul>
                    <li>Coupons</li>
                     <li>Blog Post</li>
                      <li>Return Policy</li>
                       <li>Join Affiliate</li>
                </ul>
            </div>
            <div class="footer-col-4">
                <h3>Follow Us</h3>
                <ul>
                    <li><a href="https://www.facebook.com/profile.php?id=61588204178280&locale=pt_BR">Facebook</a></li>
                    <li><a href="https://www.instagram.com/rgauto_sales/">Instagram</a></li>
                    <li><a href="#">TikTok</a></li>
                    <li><a href="#">YouTube</a></li>
                </ul>
            </div>
        </div>
        <hr>
        <p class="copyright">Copyright 2025 - RG SALES</p>
    </div>
</div>

<!------------js for toggle menu------------------->

<script>
    var MenuItems = document.getElementById("MenuItems");

   if( MenuItems.style.maxHeight == "0px");

    function menutoggle(){
    if(MenuItems.style.maxHeight == "0px"){
        MenuItems.style.maxHeight = "200px";
    } else {
        MenuItems.style.maxHeight = "0px";
    }
}
</script>
<script>
    function atualizarTotal() {
        const linhas = document.querySelectorAll("table tr:not(:first-child)");
        let total = 0;

        linhas.forEach(linha => {
            const precoText = linha.querySelector("small")?.innerText;
            const input = linha.querySelector("input");
            const subtotalTd = linha.querySelector("td:last-child");

            if (precoText && input && subtotalTd) {
                const preco = parseFloat(precoText.replace(/[^\d.]/g, ""));
                const quantidade = parseInt(input.value);
                const subtotal = preco * quantidade;

                subtotalTd.innerText = `$${subtotal.toLocaleString()}`;
                total += subtotal;
            }
        });

        document.querySelector(".total-price table").innerHTML = `
            <tr><td>Subtotal</td><td>$${total.toLocaleString()}</td></tr>
            <tr><td>Tax</td><td>$0.00</td></tr>
            <tr><td>Total</td><td>$${total.toLocaleString()}</td></tr>
        `;
    }

    // Ativa cálculo ao mudar a quantidade
    document.querySelectorAll("input[type='number']").forEach(input => {
        input.addEventListener("change", atualizarTotal);
    });

    // Ativa cálculo ao carregar a página
    window.onload = atualizarTotal;
</script>

<a
  class="wa-float"
  href="https://wa.me/258862934721?text=Olá%20RG%20Auto%20Sales,%20quero%20informações."
  target="_blank"
  rel="noopener"
  aria-label="Falar no WhatsApp com a RG Auto Sales"
>
  <i class="fa-brands fa-whatsapp"></i>
  <span>WhatsApp RG</span>
</a>

</body>
</html>