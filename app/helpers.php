<?php
function validarFecha($fecha) {
    return DateTime::createFromFormat('Y-m-d', $fecha) !== false;
}
