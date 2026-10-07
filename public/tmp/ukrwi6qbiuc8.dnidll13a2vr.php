<?php echo $this->render('partials/cabecera.html',NULL,get_defined_vars(),0); ?>

<section class="panel">
    <p class="lead">Totales por jugador, de mayor a menor. Incluye nombre, fotografía y equipo.</p>
    <p class="aviso" id="msg-goleadores" hidden></p>
    <div class="tabla-wrap">
        <table>
            <thead>
                <tr>
                    <th></th>
                    <th>Foto</th>
                    <th>Jugador</th>
                    <th>Equipo</th>
                    <th>Goles</th>
                </tr>
            </thead>
            <tbody id="tabla-goleadores"></tbody>
        </table>
    </div>
</section>

<?php echo $this->render('partials/pie.html',NULL,get_defined_vars(),0); ?>
