<?php echo $this->render('partials/cabecera.html',NULL,get_defined_vars(),0); ?>

<section class="grid-2">
    <form class="panel" id="form-equipo">
        <h2 id="titulo-form-equipo">Nuevo equipo</h2>
        <p class="ayuda">Los campos con * son obligatorios.</p>
        <input type="hidden" name="id" id="equipo-id">
        <label for="EQU_Nombre">Nombre del equipo</label>
        <input id="EQU_Nombre" name="EQU_Nombre" type="text" maxlength="80" required>
        <div class="acciones">
            <button type="submit">Guardar</button>
            <button type="button" class="secundario" id="cancelar-equipo" hidden>Cancelar</button>
        </div>
        <p class="aviso" id="msg-equipo" hidden></p>
    </form>

    <section class="panel">
        <h2>Catálogo</h2>
        <div class="tabla-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Equipo</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="tabla-equipos"></tbody>
            </table>
        </div>
    </section>
</section>

<?php echo $this->render('partials/pie.html',NULL,get_defined_vars(),0); ?>
