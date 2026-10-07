<?php echo $this->render('partials/cabecera.html',NULL,get_defined_vars(),0); ?>

<section class="panel">
    <form class="filtros" id="form-arbitro">
        <p class="ayuda">Los campos con * son obligatorios.</p>
        <label for="jornada">Jornada a jugar</label>
        <select id="jornada" name="jornada" required></select>
        <button type="submit">Ver lista</button>
    </form>
    <p class="aviso" id="msg-arbitro" hidden></p>
    <div id="lista-arbitro"></div>
</section>

<?php echo $this->render('partials/pie.html',NULL,get_defined_vars(),0); ?>
