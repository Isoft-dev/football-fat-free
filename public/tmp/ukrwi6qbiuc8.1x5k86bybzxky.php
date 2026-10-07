<?php echo $this->render('partials/cabecera.html',NULL,get_defined_vars(),0); ?>

<section class="grid-2">
    <form class="panel" id="form-incidencia">
        <h2>Nueva incidencia</h2>
        <p class="ayuda">Los campos con * son obligatorios.</p>
        <label for="JUG_Jugador">Jugador</label>
        <select id="JUG_Jugador" name="JUG_Jugador" required></select>
        <label for="INC_Tipo_Tarjeta">Tipo de tarjeta</label>
        <select id="INC_Tipo_Tarjeta" name="INC_Tipo_Tarjeta" required></select>
        <label for="INC_Descripcion">Descripción</label>
        <textarea id="INC_Descripcion" name="INC_Descripcion" maxlength="255" required></textarea>
        <label for="INC_Fecha_Incidencia">Fecha de incidencia</label>
        <input id="INC_Fecha_Incidencia" name="INC_Fecha_Incidencia" type="date" required>
        <label for="INC_Fecha_Suspension">Fecha de suspensión</label>
        <input id="INC_Fecha_Suspension" name="INC_Fecha_Suspension" type="date">
        <p class="ayuda">La fecha de suspensión solo aplica si la tarjeta es roja.</p>
        <div class="acciones">
            <button type="submit">Guardar</button>
        </div>
        <p class="aviso" id="msg-incidencia" hidden></p>
    </form>

    <section class="panel">
        <h2>Incidencias registradas</h2>
        <div class="tabla-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Jugador</th>
                        <th>Tarjeta</th>
                        <th>Suspensión</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tabla-incidencias"></tbody>
            </table>
        </div>
    </section>
</section>

<?php echo $this->render('partials/pie.html',NULL,get_defined_vars(),0); ?>
