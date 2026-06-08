<?php
require_once __DIR__ . '/../app/core/bootstrap.php';

$year = date('Y');
?>
<footer class="public-footer">
    <div class="container public-footer__grid">
        <section class="public-footer__brand" aria-label="RG Auto Sales">
            <a class="public-footer__logo" href="<?= h(public_url('index.php')) ?>">
                <img src="<?= h(asset('ImagensRG/logo.png')) ?>" alt="RG Auto Sales">
            </a>
            <p>Viaturas selecionadas, importacao acompanhada e apoio comercial para comprar ou vender com mais confianca.</p>
        </section>

        <nav class="public-footer__links" aria-label="Links rapidos">
            <h3>Links rapidos</h3>
            <ul>
                <li><a href="<?= h(public_url('index.php')) ?>">Inicio</a></li>
                <li><a href="<?= h(public_url('products.php')) ?>">Carros</a></li>
                <li><a href="<?= h(public_url('importar_carro.php')) ?>">Importar</a></li>
                <li><a href="<?= h(public_url('vender_carro.php')) ?>">Vender</a></li>
                <li><a href="<?= h(public_url('test_drive.php')) ?>">Test Drive</a></li>
                <li><a href="<?= h(public_url('contacto.php')) ?>">Contacto</a></li>
            </ul>
        </nav>

        <section class="public-footer__contact" aria-label="Contactos">
            <h3>Contactos</h3>
            <ul>
                <li><i class="fa-brands fa-whatsapp"></i><a href="https://wa.me/258862934721" target="_blank" rel="noopener">+258 862 934 721</a></li>
                <li><i class="fa-solid fa-envelope"></i><a href="mailto:rgSolutions420@gmail.com">rgSolutions420@gmail.com</a></li>
                <li><i class="fa-solid fa-location-dot"></i><span>Rua Comandante Augusto Cardoso, Maputo</span></li>
            </ul>
        </section>

        <section class="public-footer__social" aria-label="Redes sociais">
            <h3>Redes sociais</h3>
            <div class="public-footer__social-links">
                <a href="https://www.facebook.com/profile.php?id=61588204178280&locale=pt_BR" target="_blank" rel="noopener" aria-label="Facebook">
                    <i class="fa-brands fa-facebook-f"></i>
                </a>
                <a href="https://www.instagram.com/rgauto_sales/" target="_blank" rel="noopener" aria-label="Instagram">
                    <i class="fa-brands fa-instagram"></i>
                </a>
                <a href="#" aria-label="TikTok">
                    <i class="fa-brands fa-tiktok"></i>
                </a>
                <a href="#" aria-label="YouTube">
                    <i class="fa-brands fa-youtube"></i>
                </a>
            </div>
        </section>
    </div>

    <div class="container public-footer__bottom">
        <p>&copy; <?= h($year) ?> RG Auto Sales. Todos os direitos reservados.</p>
        <a href="<?= h(public_url('contacto.php')) ?>">Fale connosco</a>
    </div>
</footer>
