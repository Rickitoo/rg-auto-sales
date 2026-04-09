
<?php include(__DIR__ . "/includes/header_public.php");

include(__DIR__ . "/includes/db.php");

$carro = null;

if (isset($_GET['carro_id'])) {
    $id = (int)$_GET['carro_id'];

    $sql = "SELECT * FROM carros WHERE id = $id LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if ($res && mysqli_num_rows($res) > 0) {
        $carro = mysqli_fetch_assoc($res);
    }
}
?>

<div class="small-container" style="margin-top:40px;">

    <!-- HERO -->
    <div style="text-align:center; margin-bottom:40px;">
        <h1>Leasing Automóvel na RG Auto Sales</h1>
        <p style="max-width:600px; margin:auto;">
            Tenha o carro dos seus sonhos sem precisar pagar tudo de uma vez. 
            Com o nosso leasing, você paga em prestações acessíveis e conduz já.
        </p>
        <a class="btn btn-outline-dark btn--small" href="leasing.php?carro_id=<?= $id ?>">Leasing</a>
    </div>

    <!-- BENEFÍCIOS -->
    <div class="row">
        <div class="col-4 card" style="text-align:center;">
            <i class="fa-solid fa-money-bill-wave" style="font-size:30px;"></i>
            <h4>Sem entrada alta</h4>
            <p>Comece com valores acessíveis e sem complicações.</p>
        </div>

        <div class="col-4 card" style="text-align:center;">
            <i class="fa-solid fa-clock" style="font-size:30px;"></i>
            <h4>Pagamento flexível</h4>
            <p>Escolha prazos que cabem no seu bolso.</p>
        </div>

        <div class="col-4 card" style="text-align:center;">
            <i class="fa-solid fa-car" style="font-size:30px;"></i>
            <h4>Carros de qualidade</h4>
            <p>Viaturas verificadas e prontas para uso.</p>
        </div>

        <div class="col-4 card" style="text-align:center;">
            <i class="fa-solid fa-check-circle" style="font-size:30px;"></i>
            <h4>Aprovação rápida</h4>
            <p>Processo simples e rápido.</p>
        </div>
    </div>

    <!-- SIMULAÇÃO -->
    <div style="margin-top:60px;">
        <h2 class="title">Simular Leasing</h2>

        <form class="card" style="padding:20px; max-width:600px; margin:auto;">
            
            <label>Preço do carro (MT)</label>
            <input type="number" id="preco" placeholder="Digite o valor do carro">

            <label>Entrada (MT)</label>
            <input type="number" id="entrada" placeholder="Ex: 100000">

            <label>Prazo (meses)</label>
            <select id="meses">
                <option value="12">12 meses</option>
                <option value="24">24 meses</option>
                <option value="36">36 meses</option>
                <option value="48">48 meses</option>
            </select>

            <button type="button" class="btn" onclick="simular()">Calcular Prestação</button>

            <h3 id="resultado" style="margin-top:20px; text-align:center;"></h3>
        </form>
    </div>

    <!-- FORMULÁRIO -->
    <div style="margin-top:60px;">
        <h2 class="title">Pedir Leasing</h2>

        <form class="card" style="padding:20px; max-width:600px; margin:auto;">
            
            <label>Nome completo</label>
            <input type="text" id="nome" placeholder="Seu nome">

            <label>Telefone</label>
            <input type="text" id="telefone" placeholder="Ex: 86xxxxxxx">

            <label>Carro de interesse</label>
            <input type="text" id="carro" placeholder="Ex: Insira a marca">
            <input type="text" id="modelo" placeholder="Ex: Insira o modelo">
            value="<?= $carro ? $carro['marca'].' '.$carro['modelo'] : '' ?>" 
            placeholder="Ex: BMW X3">
            <input type="number" id="preco" 
            value="<?= $carro ? $carro['preco'] : '' ?>" 
            placeholder="Ex: 500000">
            <label>Mensagem</label>
            <textarea id="mensagem" placeholder="Deixe um comentário"></textarea>

            <button type="button" onclick="enviarLeasing()" class="btn">Enviar Pedido</button>
        </form>
    </div>

    <!-- CTA FINAL -->
    <div style="text-align:center; margin-top:60px;">
        <h2>Prefere falar direto?</h2>
        <a class="btn"
           href="https://wa.me/258862934721?text=Olá RG Auto Sales, quero fazer leasing de um carro."
           target="_blank">
            Falar no WhatsApp
        </a>
    </div>

</div>

<script>
function simular(){
    let preco = parseFloat(document.getElementById("preco").value) || 0;
    let entrada = parseFloat(document.getElementById("entrada").value) || 0;
    let meses = parseInt(document.getElementById("meses").value) || 1;

    let restante = preco - entrada;

    if(restante <= 0){
        document.getElementById("resultado").innerText = "Entrada inválida.";
        return;
    }

    // Taxa realista (podes ajustar depois)
    let taxaAnual = 0.20; // 20%
    let taxaMensal = taxaAnual / 12;

    let prestacao = (restante * taxaMensal) / (1 - Math.pow(1 + taxaMensal, -meses));

    document.getElementById("resultado").innerText =
        "Prestação: " + prestacao.toLocaleString("pt-MZ") + " MT/mês";

    // guardar valor para envio
    window.prestacaoAtual = prestacao;
}

function enviarLeasing(){

    let data = new FormData();
    let nome = document.getElementById("nome").value;
    let tel = document.getElementById("telefone").value;
    let carro = document.getElementById("carro").value;
    let msgExtra = document.getElementById("mensagem").value;

    let msg = `Olá RG Auto Sales, sou ${nome}. Quero fazer leasing para ${carro}. Tel: ${tel}. ${msgExtra}`;

    let url = "https://wa.me/258862934721?text=" + encodeURIComponent(msg);

    window.open(url, "_blank");

    data.append("carro_id", <?= $carro ? $carro['id'] : 0 ?>);
    data.append("nome", document.getElementById("nome").value);
    data.append("telefone", document.getElementById("telefone").value);
    data.append("mensagem", document.getElementById("mensagem").value);
    data.append("preco", document.getElementById("preco").value);
    data.append("entrada", document.getElementById("entrada").value);
    data.append("meses", document.getElementById("meses").value);
    data.append("prestacao", window.prestacaoAtual || 0);

    fetch("processa_leasing.php", {
        method: "POST",
        body: data
    })
    .then(res => res.text())
    .then(res => {
        if(res === "ok"){
            alert("Pedido enviado com sucesso!");
        } else {
            alert("Erro ao enviar.");
        }
    });
}
   
</script>

<?php include(__DIR__ . "/includes/footer.php"); ?>
