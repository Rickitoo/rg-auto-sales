        <footer class="rg-admin-footer">
            <strong>RG Auto Sales</strong>
            <span>Sistema administrativo | CRM ativo | Gestao comercial</span>
        </footer>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('click', function (event) {
    const toggle = event.target.closest('[data-admin-sidebar-toggle]');
    if (!toggle) {
        return;
    }

    document.body.classList.toggle('rg-admin-sidebar-open');
});
</script>
</body>
</html>
