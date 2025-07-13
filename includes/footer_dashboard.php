<?php


?>
    </main>
</div>

<script>
   
    window.BASE_URL_JS = "<?php echo rtrim(BASE_URL, '/') . '/'; ?>";
    console.log("BASE_URL_JS definida no footer:", window.BASE_URL_JS); // Para depuração
</script>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php if (strpos($_SERVER['REQUEST_URI'], '/admin/empreendimentos/unidades.php') !== false): ?>
<script src="<?php echo BASE_URL; ?>js/admin_unidades.js"></script>
<?php endif; ?>
<?php if (strpos($_SERVER['REQUEST_URI'], '/admin/empreendimentos/criar_e_editar.php') !== false): ?>
<script src="<?php echo BASE_URL; ?>js/admin_empreendimentos.js"></script>
<?php endif; ?>
<?php if (strpos($_SERVER['REQUEST_URI'], '/admin/documentos/index.php') !== false || strpos($_SERVER['REQUEST_URI'], '/admin/reservas/detalhes.php') !== false): // Carrega em docs e detalhes da reserva ?>
<script src="<?php echo BASE_URL; ?>js/admin_documentos.js"></script>
<?php endif; ?>
<script src="<?php echo BASE_URL; ?>js/admin_reservas.js"></script>
<script src="<?php echo BASE_URL; ?>js/admin_relatorios.js"></script>
<script src="<?php echo BASE_URL; ?>js/admin_vendas.js"></script>
<script src="<?php echo BASE_URL; ?>js/admin_imobiliarias.js"></script>
<script src="<?php echo BASE_URL; ?>js/admin_usuarios.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/admin_usuarios_form.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/admin_clientes.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/admin_leads.js?v=<?php echo time(); ?>"></script>
<script src="<?php echo BASE_URL; ?>js/admin.js"></script>
<script src="<?php echo BASE_URL; ?>js/forms.js"></script>

</body>
</html>