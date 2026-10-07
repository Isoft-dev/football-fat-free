<?php echo $this->render('partials/cabecera.html',NULL,get_defined_vars(),0); ?>

<section class="panel">
    <form class="filtros" id="form-rep-incidencias">
        <p class="ayuda">Los campos con * son obligatorios.</p>
        <label for="equipo">Equipo</label>
        <select id="equipo" name="equipo" required></select>
        <label for="jugador">Jugador</label>
        <select id="jugador" name="jugador">
            <option value="">Todos los jugadores</option>
        </select>
        <button type="submit">Ver reporte</button>
    </form>
    <p class="aviso" id="msg-rep-incidencias" hidden></p>
    <div id="lista-incidencias"></div>
</section>

<?php echo $this->render('partials/pie.html',NULL,get_defined_vars(),0); ?>
