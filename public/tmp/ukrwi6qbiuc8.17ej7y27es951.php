<?php echo $this->render('partials/cabecera.html',NULL,get_defined_vars(),0); ?>

<section class="grid-2">
    <form class="panel" id="form-gol">
        <h2>Registrar goles</h2>
        <p class="ayuda">Los campos con * son obligatorios.</p>
        <label for="JUG_Jugador">Jugador</label>
        <select id="JUG_Jugador" name="JUG_Jugador" required></select>
        <label for="JOR_Jornada">Jornada</label>
        <select id="JOR_Jornada" name="JOR_Jornada" required></select>
        <label for="GOL_Cantidad">Cantidad de goles</label>
        <input id="GOL_Cantidad" name="GOL_Cantidad" type="number" min="1" max="99" required>
        <p class="ayuda">Si el jugador ya tiene goles en esa jornada, se actualiza el total.</p>
        <div class="acciones">
            <button type="submit">Guardar</button>
        </div>
        <p class="aviso" id="msg-gol" hidden></p>
    </form>

    <section class="panel">
        <h2>Goles por jornada</h2>
        <div class="tabla-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Jornada</th>
                        <th>Jugador</th>
                        <th>Equipo</th>
                        <th>Goles</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tabla-goles"></tbody>
            </table>
        </div>
    </section>
</section>

<?php echo $this->render('partials/pie.html',NULL,get_defined_vars(),0); ?>
