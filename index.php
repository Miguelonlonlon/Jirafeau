<?php
/*
 *  Jirafeau, your web file repository
 *  Copyright (C) 2013
 *  Jerome Jutteau <jerome@jutteau.fr>
 *  Jimmy Beauvois <jimmy.beauvois@gmail.com>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
session_start();
define('JIRAFEAU_ROOT', dirname(__FILE__) . '/');

require(JIRAFEAU_ROOT . 'lib/settings.php');
require(JIRAFEAU_ROOT . 'lib/functions.php');
require(JIRAFEAU_ROOT . 'lib/lang.php');

check_errors($cfg);
if (has_error()) {
    show_errors();
    require(JIRAFEAU_ROOT . 'lib/template/footer.php');
    exit;
}

require(JIRAFEAU_ROOT . 'lib/template/header.php');

/* Check if user is allowed to upload. */
// First check: Challenge by IP NO PASSWORD
if (true === jirafeau_challenge_upload_ip_without_password($cfg, get_ip_address($cfg))) {
    $_SESSION['upload_auth'] = true;
    $_POST['upload_password'] = '';
    $_SESSION['user_upload_password'] = $_POST['upload_password'];

// Second check: Challenge by IP
} elseif (true === jirafeau_challenge_upload_ip($cfg, get_ip_address($cfg))) {
    // Is an upload password required?
    if (jirafeau_has_upload_password($cfg)) {
        // Logout action
        if (isset($_POST['action']) && (strcmp($_POST['action'], 'logout') == 0)) {
            session_unset();
        }

        // Challenge by password
        // …save successful logins in session
        if (isset($_POST['upload_password'])) {
            if (jirafeau_challenge_upload_password($cfg, $_POST['upload_password'])) {
                $_SESSION['upload_auth'] = true;
                $_SESSION['user_upload_password'] = $_POST['upload_password'];
            } else {
                $_SESSION['admin_auth'] = false;
                jirafeau_fatal_error(t('BAD_PSW'), $cfg);
            }
        }
        // Show login form if user session is not authorized yet
        if (true === empty($_SESSION['upload_auth'])) { ?>
            <form method="post" class="form login">
                <fieldset>
                    <table>
                        <tr>
                            <td class = "label">
                                <label for = "enter_password">
                                    <?php echo t('UP_PSW') . ':'; ?>
                                </label>
                            </td>
                        </tr><tr>
                            <td class = "field">
                                <input type = "password" name = "upload_password" id = "upload_password" size = "40" />
                            </td>
                        </tr>
                        <tr class = "nav">
                            <td class = "nav next">
                                <input type = "submit" name = "key" value ="<?php echo t('LOGIN'); ?>" />
                            </td>
                    </tr>
                    </table>
                </fieldset>
            </form>
            <?php
            require(JIRAFEAU_ROOT.'lib/template/footer.php');
            exit;
        }
    }
} else {
    jirafeau_fatal_error(t('ACCESS_KO'), $cfg); 
} ?>

<div id="upload_finished" style="display:none;">
    <fieldset>
        <legend>
            <?php echo t('FILE_UP'); ?>
        </legend>
        <div style="width:50%; margin:0 auto;">
            <img src="img/done.svg" style="width:100%;" />
        </div>
        <div id="upload_finished_download_page">
            <div id="upload_validity">
                <p><?php echo t('VALID_UNTIL'); ?>:</p>
                <p id="date"></p>
            </div>
        </div>
    </fieldset>
</div>

<div id="uploading" style="display:none;">
    <p>
        <?php echo t('UP'); ?>
        <div id="uploaded_percentage"></div>
        <div id="uploaded_speed"></div>
        <div id="uploaded_time"></div>
    </p>
</div>

<div id="error_pop" class="error" style="display:none;"></div>

<div id="upload" style="display:;">
    <fieldset>
        <legend>
            <?php echo t('SEL_FILE'); ?>
        </legend>
        <div id="file_list" style="width:100%;position:relative;">
            <p id="losfiles">
                <input type="file" id="file_select" name="file_select" multiple="multiple" size="30" onchange="control_selected_file_size(<?php echo $cfg['maximal_upload_size'] ?>, '<?php  if ($cfg['maximal_upload_size'] >= 1024) { echo t('2_BIG') . ', ' . t('FILE_LIM') . " " . number_format($cfg['maximal_upload_size']/1024, 2) . " GB."; } elseif ($cfg['maximal_upload_size'] > 0) { echo t('2_BIG') . ', ' . t('FILE_LIM') . " " . $cfg['maximal_upload_size'] . " MB."; } ?>')"/>
            </p>
            <table id="email_table" style="margin:0 auto;width:100%;display:none;">
                <tr>
                    <td colspan="2" align="center" style="height: 3em;">
                    <small>Completa todos los campos y pulsa "Enviar" para compartir los archivos.</small><br/>
                    <small style="font-size:8px;">Mantén el cursor sobre cualquier campo para ver la ayuda</small>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="text" readonly onfocus="this.removeAttribute('readonly');" id="tuNombre" name="tuNombre" placeholder="Escribe aquí tu nombre" required style="width: 100%;" value=""/>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="text" readonly onfocus="this.removeAttribute('readonly');" onchange="ValidaEmails();" id="tuMail" name="tuMail" placeholder="Escribe aquí tu email" required style="width: 100%;" value=""/>
                    </td>
                </tr>
                <tr title="Para mandar el enlace a varios
destinatarios, introduce las
direcciones separadas por un
espacio en blanco.
No es necesario ponerte a tí
mismo en este campo, recibes
igualmente tu copia de los datos
en tu email.">
                    <td colspan="2" style="width:auto;">
                        <input type="text" readonly onfocus="this.removeAttribute('readonly');" onchange="ValidaEmails();" id="destMail" name="destMail" placeholder="Escribe aquí el email de destino" required style="width:100%;" />
                    </td>
                    <!--td style="width:50px; text-align:right;">
                        <img src="<?php echo 'media/' . $cfg['style'] . '/plus.png'; ?>" alt="Añadir email" title="Pulsa aquí para añadir otro email" />
                    </td-->
                </tr>
                <tr title="Escribe aquí un mensaje
que tus destinatarios recibirán
en el mismo correo que les notifica
tu envío.">
                    <td colspan="2">
                        <textarea id="tuMensaje" name="tuMensaje" placeholder="Envía un mensaje personalizado a tu destinatario" required style="width: 100%; height:10em;"></textarea>
                    </td>
                </tr>
            </table>
        </div>
        <div id="options" style="display:none;">
            <table id="option_table" style="margin:0 auto;width:100%;">
                <?php if ($cfg['one_time_download']) {
                    echo '<tr title="Si marcas esta opción, cuando se descarguen
tus archivos automáticamente se eliminarán 
imposibilitando así cualquier descarga adicional.
Si vas a mandar el enlace a varios destinatarios
no debes activar esta opción."><td>' . t('ONE_TIME_DL') . ':</td>';
                    echo '<td><input type="checkbox" id="one_time_download"/></td></tr>';
                } ?>
                <tr title="Si se establece una contraseña
habrá que introducirla para
efectuar la descarga, de esta
forma si tu enlace llega a malas
manos, si no conoce la clave le 
será imposible descargarlo">
                    <td>
                        <label for="input_key"><?php echo t('PSW') . ':'; ?></label>
                    </td>
                    <td>
                        <input type="password" readonly onfocus="this.removeAttribute('readonly');" name="key" id="input_key" style="width:95%;" placeholder="Protege la descarga con clave" />
                    </td>
                </tr>
                <tr style="display:none;">
                    <td>
                        <label for="select_time"><?php echo t('TIME_LIM') . ':'; ?></label>
                    </td>
                    <td>
                        <select name="time" id="select_time">
                        <?php
                        $expirationTimeOptions = array(
                        array(
                            'value' => 'minute',
                            'label' => '1_MIN'
                        ),
                        array(
                            'value' => 'hour',
                            'label' => '1_H'
                        ),
                        array(
                            'value' => 'day',
                            'label' => '1_D'
                        ),
                        array(
                            'value' => 'week',
                            'label' => '1_W'
                        ),
                        array(
                            'value' => 'month',
                            'label' => '1_M'
                        ),
                        array(
                            'value' => 'quarter',
                            'label' => '1_Q'
                        ),
                        array(
                            'value' => 'year',
                            'label' => '1_Y'
                        ),
                        array(
                            'value' => 'none',
                            'label' => 'NONE'
                        )
                        );
                        foreach ($expirationTimeOptions as $expirationTimeOption) {
                            $selected = ($expirationTimeOption['value'] === $cfg['availability_default'])? 'selected="selected"' : '';
                            if (true === $cfg['availabilities'][$expirationTimeOption['value']]) {
                                echo '<option value="' . $expirationTimeOption['value'] . '" ' .
                            $selected . '>' . t($expirationTimeOption['label']) . '</option>';
                            }
                        }
                        ?>
                        </select>
                        <input type="hidden" id="grupo" name="grupo" value="<?php echo time() ?>"/>
                    </td>
                </tr>
                <?php if ($cfg['maximal_upload_size'] >= 1024) {
                    echo '<tr class="config"><td colspan="2">' . t('FILE_LIM');
                    echo " " . number_format($cfg['maximal_upload_size'] / 1024, 2) . " GB.</td></tr>";
                } elseif ($cfg['maximal_upload_size'] > 0) {
                    echo '<tr class="config"><td colspan="2">' . t('FILE_LIM');
                    echo " " . $cfg['maximal_upload_size'] . " MB.</td></tr>";
                } else {
                    echo '<tr style="display:none;"><td class="config" colspan="2"></td></tr>';
                } ?>
                <tr>
                    <td id="max_file_size" class="config"></td>
                    <td align="right">
                        <?php
                        if (jirafeau_has_upload_password($cfg) && $_SESSION['upload_auth']) {
                            ?>
                        <input type="hidden" id="upload_password" name="upload_password" value="<?php echo $_SESSION['user_upload_password'] ?>"/>
                        <?php } else { ?>
                        <input type="hidden" id="upload_password" name="upload_password" value=""/>
                        <?php } ?>
                        <input type="submit" id="send" value="<?php echo t('SEND'); ?>"
                        onclick="document.getElementById('upload').style.display = 'none'; document.getElementById('uploading').style.display = ''; upload (<?php echo jirafeau_get_max_upload_size_bytes(); ?>, document.getElementById('file_select').files.length);" style="display:none;" disabled="disabled" />
                    </td>
                </tr>
            </table>
        </div>
    </fieldset>

    <?php if (jirafeau_has_upload_password($cfg)
        && false === jirafeau_challenge_upload_ip_without_password($cfg, get_ip_address($cfg))) { ?>
    <form method="post" class="form logout">
        <input type = "hidden" name = "action" value = "logout"/>
        <input type = "submit" value = "<?php echo t('LOGOUT'); ?>" />
    </form>
    <?php } ?>
</div>

<script type="text/javascript" lang="Javascript">
// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-v3-or-Later
    document.getElementById('error_pop').style.display = 'none';
    document.getElementById('uploading').style.display = 'none';
    document.getElementById('upload_finished').style.display = 'none';
    document.getElementById('options').style.display = 'none';
    document.getElementById('send').style.display = 'none';
    if (!check_html5_file_api ())
        document.getElementById('max_file_size').innerHTML = '<?php
             echo t('NO_BROWSER_SUPPORT') . jirafeau_get_max_upload_size();
             ?>';

    addCopyListener('upload_link_button', 'upload_link');
    addCopyListener('preview_link_button', 'preview_link');
    addCopyListener('direct_link_button', 'direct_link');
    addCopyListener('delete_link_button', 'delete_link');
// @license-end
</script>
<?php require(JIRAFEAU_ROOT . 'lib/template/footer.php'); ?>
