<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Spanish strings for mod_pdfdocument.
 *
 * @package    mod_pdfdocument
 * @copyright  2026 Web Programacion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Documento PDF';
$string['modulename'] = 'Documento PDF';
$string['modulenameplural'] = 'Documentos PDF';
$string['modulename_help'] = 'La actividad Documento PDF permite al profesor compartir un PDF que el alumnado puede leer en pantalla a través de un visor protegido, pero que no puede descargar por medios habituales. Nota: ninguna tecnología web puede impedir por completo la captura de pantalla; esta actividad elimina las vías fáciles de descarga y, opcionalmente, añade una marca de agua con la identidad del lector en cada página.';
$string['pluginadministration'] = 'Administración de Documento PDF';

// Form.
$string['name'] = 'Nombre';
$string['pdffile'] = 'Fichero PDF';
$string['pdffile_help'] = 'Sube un único fichero PDF. El alumnado lo verá en el visor protegido en pantalla.';
$string['watermark'] = 'Marca de agua con la identidad del lector en cada página';
$string['watermark_help'] = 'Si se activa, cada página se superpone con una marca de agua diagonal semitransparente que muestra el nombre completo y el correo del usuario que la visualiza, para disuadir la redistribución de capturas de pantalla.';
$string['erroremptypdf'] = 'Debes subir un fichero PDF.';

// Viewer.
$string['loading'] = 'Cargando documento…';
$string['page'] = 'Página';
$string['of'] = 'de';
$string['previouspage'] = 'Página anterior';
$string['nextpage'] = 'Página siguiente';
$string['zoomin'] = 'Acercar';
$string['zoomout'] = 'Alejar';
$string['fitwidth'] = 'Ajustar al ancho';
$string['errordisplay'] = 'Este documento no se puede mostrar.';
$string['nofile'] = 'Todavía no se ha subido ningún fichero PDF para esta actividad.';

// Capabilities.
$string['pdfdocument:addinstance'] = 'Añadir un nuevo Documento PDF';
$string['pdfdocument:view'] = 'Ver el Documento PDF';

// Privacy.
$string['privacy:metadata'] = 'El plugin Documento PDF no almacena ningún dato personal. Las visualizaciones se registran mediante el sistema de registro estándar de Moodle.';

// Events.
$string['eventcoursemoduleviewed'] = 'Documento PDF visualizado';
