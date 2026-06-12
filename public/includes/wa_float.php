<?php
$waFloatPhone = '258862934721';
$waFloatText = $waFloatText ?? 'Olá RG Auto Sales, quero informações.';
$waFloatHref = $waFloatHref ?? ('https://wa.me/' . $waFloatPhone . '?text=' . rawurlencode($waFloatText));
?>
<a class="wa-float"
   href="<?= h($waFloatHref) ?>"
   target="_blank"
   rel="noopener"
   aria-label="Falar no WhatsApp com a RG Auto Sales">
    <i class="fa-brands fa-whatsapp"></i>
    <span>WhatsApp RG</span>
</a>
