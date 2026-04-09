<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="ImagensRG/logo.png">
    <title>account-page</title>
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

    <!------------account-page---------->

    <div class="account-page">
        <div class="container">
            <div class="row">
                <div class="col-2">
                    <a href="index.php"><img src="ImagensRG/logo.png" width="100%"></a>
                </div>

                <div class="col-2">
                    <div class="form-container">
                        <div class="form-btn">
                            <span onclick="login()">Login</span>
                            <span onclick="register()">Register</span>
                            <hr id="Indicator">
                        </div>
                        <form id="LoginForm">
                            <input type="text" id="login_username" placeholder="Username">
                            <input type="password" id="login_password" placeholder="Password">
                            <button type="button" class="btn" onclick="validarLogin()">Login</button>
                        </form>

                        <form id="RegForm">
                            <input type="text" id="reg_username" placeholder="Username">
                            <input type="email" id="reg_email" placeholder="Email">
                            <input type="password" id="reg_password" placeholder="Password">
                            <button type="button" class="btn" onclick="registar()">Register</button>
                        </form>

                    </div>
                </div>
            </div>
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

    MenuItems.style.maxHeight = "0px";
    
    function menutoggle(){
        if(MenuItems.style.maxHeight == "0px"){
            MenuItems.style.maxHeight = "200px";
        } else {
            MenuItems.style.maxHeight = "0px";
        }
    }
</script>


<!----------js for toggle Form-->

<script>
    var LoginForm = document.getElementById("LoginForm");
    var RegForm = document.getElementById("RegForm");
    var Indicator = document.getElementById("Indicator");

    function register(){
        RegForm.style.transform = "translateX(0px)";
        LoginForm.style.transform = "translateX(0px)";
        Indicator.style.transform = "translateX(100px)";

    }
    function login(){
        RegForm.style.transform = "translateX(300px)";
        LoginForm.style.transform = "translateX(300px)";
        Indicator.style.transform = "translateX(0px)";
    }

</script>
<script>
   function validarLogin(){
    let data = new FormData();
    data.append("username", document.getElementById("login_username").value);
    data.append("password", document.getElementById("login_password").value);

    fetch("processa_login.php", {
        method: "POST",
        body: data
    })
    .then(res => res.text())
    .then(res => {
        if(res === "ok"){
            window.location.href = "index.php";
        } else {
            alert("Login inválido!");
        }
    });
}

    function registar(){
    let data = new FormData();
    data.append("username", document.getElementById("reg_username").value);
    data.append("email", document.getElementById("reg_email").value);
    data.append("password", document.getElementById("reg_password").value);

    fetch("processa_registo.php", {
        method: "POST",
        body: data
    })
    .then(res => res.text())
    .then(res => {
        if(res === "ok"){
            alert("Conta criada!");
        } else {
            alert("Erro ao registar.");
        }
    });
}
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