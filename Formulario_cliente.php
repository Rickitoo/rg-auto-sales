<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include("conexao.php");

function clean($s){
    return trim((string)$s);
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: test_drive.html");
    exit;
}

$nome      = clean($_POST['nome'] ?? '');
$email     = clean($_POST['email'] ?? '');
$telefone  = clean($_POST['telefone'] ?? '');
$sexo      = clean($_POST['sexo'] ?? '');
$data      = clean($_POST['data'] ?? '');
$hora      = clean($_POST['hora'] ?? '');
$marca     = clean($_POST['marca'] ?? '');
$modelo    = clean($_POST['modelo'] ?? '');
$ano       = (int)($_POST['ano'] ?? 0);
$mensagem  = clean($_POST['mensagem'] ?? '');

if ($nome=='' || $telefone=='' || $marca=='' || $modelo=='' || $ano<=0) {
    die("Preencha os campos obrigatórios.");
}

// 1️⃣ Guardar no banco
$stmt = mysqli_prepare($conexao, "
    INSERT INTO leads 
    (tipo, nome, telefone, email, mensagem, marca, modelo, ano, origem, status)
    VALUES ('testdrive', ?, ?, ?, ?, ?, ?, ?, 'site', 'novo')
");

mysqli_stmt_bind_param(
    $stmt,
    "ssssssi",
    $nome,
    $telefone,
    $email,
    $mensagem,
    $marca,
    $modelo,
    $ano
);

if (!mysqli_stmt_execute($stmt)) {
    die("Erro ao salvar lead: " . mysqli_stmt_error($stmt));
}

$lead_id = mysqli_insert_id($conexao);
mysqli_stmt_close($stmt);

// 2️⃣ Redirecionar para WhatsApp
// 2️⃣ Montar mensagem (normal) e URL-encode no final
$msg  = "LEAD #$lead_id (Test Drive)\n";
$msg .= "Nome: $nome\n";
$msg .= "Telefone: $telefone\n";
if ($email !== '') $msg .= "Email: $email\n";
$msg .= "Carro: $marca $modelo ($ano)\n";
$msg .= "Data: $data às $hora\n";
if ($mensagem !== '') $msg .= "Obs: $mensagem\n";

$numeroRG = "258862934721"; //  número RG
$url = "https://wa.me/$numeroRG?text=" . rawurlencode($msg);

header("Location: $url");
exit;
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" type="image/png" href="ImagensRG/logo.png" />
  <title>Agendar Test Drive - RG Auto Sales</title>

  <link rel="stylesheet" href="style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>

<body>
  <!-- HEADER (Opção A) -->
  <header class="header header--rg">
    <div class="header__overlay">
      <div class="container">

        <div class="navbar">
          <div class="logo">
            <a href="index.html">
              <img src="ImagensRG/logo.png" alt="RG Auto Sales" width="120" />
            </a>
          </div>

          <nav>
            <ul id="MenuItems">
              <li><a href="index.html">Início</a></li>
              <li><a href="products.html">Carros</a></li>
              <li><a href="about.html">Sobre</a></li>
              <li><a href="contacto.html">Contacto</a></li>
              <li><a href="account.html">Conta</a></li>
              <li><a href="test_drive.html">Test Drive</a></li>
              <li><a href="leasing.html">Leasing</a></li>
              <li><a href="vender_carro.html">Vender</a></li>
            </ul>
          </nav>

          <a href="cart.html" aria-label="Carrinho">
            <img src="ImagensRG/png-transparent-computer-icons-shopping-cart-basket-shopping-cart-text-hand-share-icon.png" alt="Carrinho" width="28" height="30" />
          </a>

          <button class="menu-icon" type="button" onclick="menutoggle()" aria-label="Abrir menu">
            <i class="fa-solid fa-bars"></i>
          </button>
        </div>

        <div class="row header__hero">
          <div class="col-2">
            <h1>Agendar Test Drive</h1>
            <p>Preencha os dados e a RG confirma o seu horário.</p>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
              <a class="btn" href="products.html">Ver Carros</a>
              <a class="btn btn--outline" href="https://wa.me/258862934721?text=Olá%20RG%20Auto%20Sales,%20quero%20agendar%20um%20test%20drive." target="_blank" rel="noopener">WhatsApp</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- FORM -->
  <div class="small-container">
    <h2 class="title">Formulário</h2>

    <div class="card" style="padding:18px;">
      <form id="formulario" action="Formulario_cliente.php" method="POST">
        <div class="row" style="margin-top:0; align-items:flex-start;">
          <div class="col-2">
            <label for="nome"><strong>Nome</strong></label>
            <input type="text" name="nome" id="nome" required placeholder="Nome completo" />

            <label for="email" style="margin-top:12px; display:block;"><strong>Email</strong></label>
            <input type="email" name="email" id="email" placeholder="opcional" />

            <label for="telefone" style="margin-top:12px; display:block;"><strong>WhatsApp/Telefone</strong></label>
            <input type="tel" name="telefone" id="telefone" placeholder="+258 ..." required />
          </div>

          <div class="col-2">
            <label for="sexo"><strong>Sexo</strong></label>
            <select id="sexo" name="sexo" required>
              <option value="">Selecione</option>
              <option value="femenino">Feminino</option>
              <option value="masculino">Masculino</option>
            </select>

            <label for="data" style="margin-top:12px; display:block;"><strong>Data</strong></label>
            <input type="date" name="data" id="data" required />

            <label for="hora" style="margin-top:12px; display:block;"><strong>Hora</strong></label>
            <input type="time" name="hora" id="hora" required />

            <label for="marca" style="margin-top:12px; display:block;"><strong>Marca</strong></label>
            <select id="marca" name="marca" required>
            <option value="">Selecione a marca</option>
            </select>

            <label for="modelo" style="margin-top:12px; display:block;"><strong>Modelo</strong></label>
            <select id="modelo" name="modelo" required disabled>
            <option value="">Selecione o modelo</option>
            </select>

            <label for="ano" style="margin-top:12px; display:block;"><strong>Ano</strong></label>
            <select id="ano" name="ano" required disabled>
            <option value="">Selecione o ano</option>
            </select>

          </div>
        </div>

        <label for="mensagem" style="margin-top:12px; display:block;"><strong>Observações (opcional)</strong></label>
        <textarea id="mensagem" name="mensagem" rows="4" placeholder="Ex.: Quero levar meu pai, preferimos manhã..."></textarea>

        <div class="product-actions" style="justify-content:flex-start; margin-top:14px;">
          <button type="submit" class="btn">Agendar</button>
          <button type="button" id="btnWhats" class="btn btn--outline">Enviar via WhatsApp</button>
          <a class="btn btn--outline" href="index.html">Voltar</a>
        </div>

        <p style="margin-top:10px; color:#01203f;">
          <strong>Nota:</strong> Ao clicar “Enviar via WhatsApp”, a mensagem vai com os dados preenchidos.
        </p>
      </form>
    </div>
  </div>

  <!-- WhatsApp flutuante -->
  <a class="wa-float"
     href="https://wa.me/258862934721?text=Olá%20RG%20Auto%20Sales,%20quero%20agendar%20um%20test%20drive."
     target="_blank" rel="noopener"
     aria-label="Falar no WhatsApp com a RG Auto Sales">
    <i class="fa-brands fa-whatsapp"></i>
    <span>WhatsApp RG</span>
  </a>

  <div class="footer">
    <div class="container">
      <div class="row">

        <div class="footer-col-1">
          <h3>Download do App</h3>
          <p>Disponível para Android e iOS.</p>
          <div class="app-logo">
            <img src="ImagensRG/AppStore.png" alt="App Store" />
            <img src="ImagensRG/pngtree-google-play-store-vector-png-image_9183318.png" alt="Google Play" />
          </div>
        </div>

        <div class="footer-col-2">
          <img src="ImagensRG/logo.png" alt="RG Auto Sales" />
          <p>Nosso objetivo é tornar acessível o prazer de dirigir veículos de qualidade, com transparência e confiança.</p>
        </div>

        <div class="footer-col-1">
          <h3>Links úteis</h3>
          <ul>
            <li><a href="products.html">Carros</a></li>
            <li><a href="Test_drive.html">Agendar Test Drive</a></li>
            <li><a href="vender_carro.html">Vender viatura</a></li>
            <li><a href="contacto.html">Contactos</a></li>
          </ul>
        </div>

        <div class="footer-col-4">
          <h3>Siga a RG</h3>
          <ul>
            <li><a href="https://www.facebook.com/profile.php?id=61588204178280&locale=pt_BR">Facebook</a></li>
            <li><a href="https://www.instagram.com/rgauto_sales/">Instagram</a></li>
            <li><a href="#">TikTok</a></li>
            <li><a href="#">YouTube</a></li>
          </ul>
        </div>

      </div>

      <hr />
      <p class="copyright">Copyright 2026 - RG SALES</p>
    </div>
  </div>


  <!-- JS Menu + WhatsApp -->
  <script>
    const menuItems = document.getElementById("MenuItems");
    function menutoggle(){ menuItems.classList.toggle("show"); }

    // Data mínima como hoje
    (function(){
      const d = new Date();
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth()+1).padStart(2,'0');
      const dd = String(d.getDate()).padStart(2,'0');
      const today = `${yyyy}-${mm}-${dd}`;
      const dateInput = document.getElementById("data");
      if(dateInput) dateInput.min = today;
    })();

    // Catálogo base ( ir aumentando)
    const CAR_DATA = {
      "Toyota": {
        "Hilux": [2018,2019,2020,2021,2022,2023,2024,2025],
        "Corolla": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Fortuner": [2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Land Cruiser": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      },
      "Mercedes-Benz": {
        "C-Class": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "E-Class": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "GLE": [2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "AMG GT": [2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      },
      "BMW": {
        "M3": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "M4": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "X5": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "M2": [2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      },
      "Audi": {
        "A4": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "A6": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Q5": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "RS3": [2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      },
      "Ford": {
        "Ranger": [2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Raptor": [2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Everest": [2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Mustang": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      },
      "Volkswagen": {
        "Golf": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Golf R": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Polo": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Tiguan": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      },
      "Porsche": {
        "911": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "GT3 RS": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Cayenne": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Macan": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      },
      "Lamborghini": {
        "Huracan": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Urus": [2018,2019,2020,2021,2022,2023,2024,2025],
        "Aventador": [2014,2015,2016,2017,2018,2019,2020,2021,2022],
        "Revuelto": [2024,2025]
      },
      "Ferrari": {
        "SF90": [2020,2021,2022,2023,2024,2025],
        "488": [2016,2017,2018,2019,2020],
        "F8 Tributo": [2020,2021,2022],
        "Roma": [2021,2022,2023,2024,2025]
      },
      "Nissan": {
        "Patrol": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Navara": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "X-Trail": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      },
      "Mahindra": {
        "Scorpio": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "XUV": [2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025],
        "Bolero": [2014,2015,2016,2017,2018,2019,2020,2021,2022,2023,2024,2025]
      }
    };

    const marcaEl = document.getElementById("marca");
    const modeloEl = document.getElementById("modelo");
    const anoEl = document.getElementById("ano");

    // Preencher marcas
    function loadBrands(){
      Object.keys(CAR_DATA).sort().forEach(brand => {
        const opt = document.createElement("option");
        opt.value = brand;
        opt.textContent = brand;
        marcaEl.appendChild(opt);
      });
    }

    function resetSelect(selectEl, placeholder){
      selectEl.innerHTML = "";
      const opt = document.createElement("option");
      opt.value = "";
      opt.textContent = placeholder;
      selectEl.appendChild(opt);
    }

    // Ao escolher marca -> carregar modelos
    marcaEl.addEventListener("change", () => {
      const brand = marcaEl.value;

      resetSelect(modeloEl, "Selecione o modelo");
      resetSelect(anoEl, "Selecione o ano");

      anoEl.disabled = true;
      modeloEl.disabled = !brand;

      if(!brand) return;

      if(!brand){
        modeloEl.disabled = true;
        anoEl.disabled = true;
        resetSelect(modeloEl, "Selecione a marca primeiro");
        resetSelect(anoEl, "Selecione o modelo primeiro");
        return;
      }

      Object.keys(CAR_DATA[brand]).sort().forEach(model => {
        const opt = document.createElement("option");
        opt.value = model;
        opt.textContent = model;
        modeloEl.appendChild(opt);
      });
    });

    // Ao escolher modelo -> carregar anos
    modeloEl.addEventListener("change", () => {
      const brand = marcaEl.value;
      const model = modeloEl.value;

      resetSelect(anoEl, "Selecione o ano");
      anoEl.disabled = !(brand && model);

      if(!(brand && model)) return;

      const years = CAR_DATA[brand][model] || [];
      years.slice().sort((a,b) => b-a).forEach(y => {
        const opt = document.createElement("option");
        opt.value = String(y);
        opt.textContent = String(y);
        anoEl.appendChild(opt);
      });
    });

    loadBrands();

    // Botão WhatsApp com dados do formulário
    document.getElementById("btnWhats").addEventListener("click", function(){
      const nome = document.getElementById("nome").value.trim();
      const email = document.getElementById("email").value.trim();
      const tel = document.getElementById("telefone").value.trim();
      const sexo = document.getElementById("sexo").value;
      const data = document.getElementById("data").value;
      const hora = document.getElementById("hora").value;

      const marca = marcaEl.value;
      const modelo = modeloEl.value;
      const ano = anoEl.value;

      const obs = document.getElementById("mensagem").value.trim();

      if(!nome  || !tel || !sexo || !data || !hora || !marca || !modelo || !ano){
        alert("Preencha todos os campos obrigatórios antes de enviar no WhatsApp.");
        return;
      }

      const carroCompleto = `${marca} ${modelo} (${ano})`;

      const msg =
        `Olá RG Auto Sales, quero agendar um Test Drive.\n\n` +
        `Nome: ${nome}\n` +
        (email ? `Email: ${email}\n` : "") +
        `Telefone: ${tel}\n` +
        `Sexo: ${sexo}\n` +
        `Carro: ${carroCompleto}\n` +
        `Data: ${data}\n` +
        `Hora: ${hora}\n` +
        (obs ? `Obs: ${obs}\n` : "");

      const wa = "https://wa.me/258862934721?text=" + encodeURIComponent(msg);
      window.open(wa, "_blank");
    });
  </script>

</body>
</html>
