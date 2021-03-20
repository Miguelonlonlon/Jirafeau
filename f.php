<?php
/*
 *  Jirafeau, your web file repository
 *  Copyright (C) 2008  Julien "axolotl" BERNARD <axolotl@magieeternelle.org>
 *  Copyright (C) 2015  Jerome Jutteau <jerome@jutteau.fr>
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
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
define('JIRAFEAU_ROOT', dirname(__FILE__) . '/');

require(JIRAFEAU_ROOT . 'lib/settings.php');
require(JIRAFEAU_ROOT . 'lib/config.local.php');
require(JIRAFEAU_ROOT . 'lib/functions.php');
require(JIRAFEAU_ROOT . 'lib/lang.php');

if (!isset($_GET['h']) || empty($_GET['h'])) {
    header('Location: ./');
    exit;
}

/* Operations may take a long time.
 * Be sure PHP's safe mode is off.
 */
@set_time_limit(0);

$link_name = $_GET['h'];

if (!preg_match('/[0-9a-zA-Z_-]+$/', $link_name)) {
    require(JIRAFEAU_ROOT.'lib/template/header.php');
    echo '<div class="error"><p>' . t('FILE_404') . '</p></div>';
    require(JIRAFEAU_ROOT.'lib/template/footer.php');
    exit;
}

$link = jirafeau_get_link($link_name);
if (count($link) == 0) {
    require(JIRAFEAU_ROOT.'lib/template/header.php');
    echo '<div class="error"><p>' . t('FILE_404') .
    '</p></div>';
    require(JIRAFEAU_ROOT.'lib/template/footer.php');
    exit;
}

$delete_code = '';
if (isset($_GET['d']) && !empty($_GET['d']) &&  $_GET['d'] != '1') {
    $delete_code = $_GET['d'];
}

$crypt_key = '';
if (isset($_GET['k']) && !empty($_GET['k'])) {
    $crypt_key = $_GET['k'];
}

$do_download = false;
if (isset($_GET['d']) && $_GET['d'] == '1') {
    $do_download = true;
}

$do_preview = false;
if (isset($_GET['p']) && !empty($_GET['p'])) {
    $do_preview = true;
}

$esgrupo = false;
if (jirafeau_is_group($link_name)) {
    $esgrupo = true;
} else {
    $p = s2p($link['hash']);
    if (!file_exists(VAR_FILES . $p . $link['hash'])) {
        jirafeau_delete_link($link_name);
        require(JIRAFEAU_ROOT.'lib/template/header.php');
        echo '<div class="error"><p>'.t('FILE_NOT_AVAIL').
        '</p></div>';
        require(JIRAFEAU_ROOT.'lib/template/footer.php');
        exit;
    }
}

if (!empty($delete_code) && $delete_code == $link['link_code']) {
    require(JIRAFEAU_ROOT.'lib/template/header.php');
    if (isset($_POST['do_delete'])) {
        jirafeau_delete_link($link_name);
        echo '<div class="message"><p>'.t('FILE_DELETED').
            '</p></div>';
    } else { ?>
        <div>
        <form action="f.php" method="post" id="submit_delete_post" class="form login">
        <input type="hidden" name="do_delete" value=1/>
        <fieldset>
             <legend> <?php echo t('CONFIRM_DEL') ?> </legend>
             <table>
             <tr><td>
             <?php if ($esgrupo) {
                echo t('GONNA_DEL') . ' "' . jirafeau_zipname($link_name) . '"';
             } else {
                echo t('GONNA_DEL') . ' "' . jirafeau_escape($link['file_name']) . '" (' . jirafeau_human_size($link['file_size']) . ').';
             } ?>
             </td></tr>
             <tr><td>
                <?php echo t('USING_SERVICE'). ' <a href="tos.php" target="_blank" rel="noopener noreferrer">' . t('TOS') . '</a>.' ?>
             </td></tr>
             <tr><td>
                <input type="submit" id="submit_delete" value="<?php echo t('DELETE'); ?>"
                onclick="document.getElementById('submit_delete_post').action='<?php echo 'f.php?h=' . $link_name . '&amp;d=' . $delete_code . "';"; ?>
                document.getElementById('submit_delete_post').submit();"/>
             </td></tr>
             </table>
         </fieldset></form></div><?php
    }
    require(JIRAFEAU_ROOT.'lib/template/footer.php');
    exit;
}

if ($link['time'] != JIRAFEAU_INFINITY && time() > $link['time']) {
    jirafeau_delete_link($link_name);
    require(JIRAFEAU_ROOT.'lib/template/header.php');
    echo '<div class="error"><p>'.
    t('FILE_EXPIRED') . ' ' .
    t('FILE_DELETED') .
    '</p></div>';
    require(JIRAFEAU_ROOT . 'lib/template/footer.php');
    exit;
}

if (empty($crypt_key) && $link['crypted']) {
    require(JIRAFEAU_ROOT.'lib/template/header.php');
    echo '<div class="error"><p>' . t('FILE_404') .
    '</p></div>';
    require(JIRAFEAU_ROOT.'lib/template/footer.php');
    exit;
}

$password_challenged = false;
if (!empty($link['key'])) {
    if (!isset($_POST['key'])) {
        require(JIRAFEAU_ROOT.'lib/template/header.php');
        echo '<div>' .
             '<form action="f.php" method="post" id="submit_post" class="form login">'; ?>
             <input type = "hidden" name = "jirafeau" value = "<?php echo JIRAFEAU_VERSION ?>"/><?php
        echo '<fieldset>' .
             '<legend>' . t('PSW_PROTEC') .
             '</legend><table><tr><td>' .
             t('GIMME_PSW') . ' : ' .
             '<input type = "password" name = "key" />' .
             '</td></tr>' .
             '<tr><td>' .
             t('USING_SERVICE'). ' <a href="tos.php" target="_blank" rel="noopener noreferrer">' . t('TOS') . '</a>.' .
             '</td></tr>';

        if ($link['onetime'] == 'O') {
            echo '<tr><td id="self_destruct">' .
                 t('AUTO_DESTRUCT') .
                 '</td></tr>';
        } ?><tr><td><input type="submit" id = "submit_download"  value="<?php echo t('DL'); ?>"
        onclick="document.getElementById('submit_post').action='<?php
        echo 'f.php?h=' . $link_name . '&amp;d=1';
        if (!empty($crypt_key)) {
            echo '&amp;k=' . urlencode($crypt_key);
        } ?>';
        document.getElementById('submit_post').submit();"/><?php
        if ($cfg['preview'] && jirafeau_is_viewable($link['mime_type'])) {
            ?><input type="submit" id = "submit_preview"  value="<?php echo t('PREVIEW'); ?>"
            onclick="document.getElementById('submit_post').action='<?php
            echo 'f.php?h=' . $link_name . '&amp;p=1';
            if (!empty($crypt_key)) {
                echo '&amp;k=' . urlencode($crypt_key);
            } ?>';
            document.getElementById('submit_post').submit();"/><?php
        }
        echo '</td></tr></table></fieldset></form></div>';
        require(JIRAFEAU_ROOT.'lib/template/footer.php');
        exit;
    } else {
        if ($link['key'] == md5($_POST['key'])) {
            $password_challenged = true;
        } else {
            sleep(2);
            require(JIRAFEAU_ROOT.'lib/template/header.php');
            echo '<div class="error"><p>' . t('ACCESS_KO') .
            '</p></div>';
            require(JIRAFEAU_ROOT.'lib/template/footer.php');
            exit;
        }
    }
}

if (!$password_challenged && !$do_download && !$do_preview) {
    require(JIRAFEAU_ROOT.'lib/template/header.php'); ?>
    <div>
        <form action="f.php" method="post" id="submit_post" class="form download">
        <input type = "hidden" name = "jirafeau" value = "<?php echo JIRAFEAU_VERSION ?>"/>
        <fieldset>
            <legend>
                <?php echo t('DL_PAGE') ?>
            </legend>
            <table style="width:100%;margin:0 auto;">
                <tr>
                    <td style="padding-bottom:10px;">
                        <?php echo jirafeau_escape($link['file_name']) ?>
                    </td>
                    <td style="width:40px;padding-bottom:10px;">
                        <a href="f.php?h=<?php echo $link_name ?>&amp;d=1<?php if (!empty($crypt_key)) { ?>&amp;k=<?php echo urlencode($crypt_key); } ?>" target="_BLANK"><img src="img/download.svg" style="width:40px;border:none;" title="<?php echo t('DL') . ' ' . $link['file_name'] ?>" alt="<?php echo t('DL') . ' ' . $link['file_name'] ?>" onmouseover="javascript: this.src='img/download_activ.svg'" onmouseout="javascript: this.src='img/download.svg'" /></a>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <div style="width:90%;margin:0 auto;padding-bottom:5px;"> Contenido del archivo</div>
                        <table style="width:90%;margin:0 auto;border:dotted 1px #fff;border-radius: 8px;">
                          <tbody>
                                <tr>
                                    <td style="text-align:left;padding-left:5px;">
                                        <?php echo t('FILENAME') ?>
                                    </td>
                                    <td style="width:30px;padding-right:5px;text-align:center;">
                                        <img src="img/download_head.svg" style="width:25px;border:none;" alt="<?php echo t('DL') ?>" />
                                    </td>
                                </tr>
                                <?php $laclac = file(VAR_GROUPS . $link_name, FILE_IGNORE_NEW_LINES);
                                foreach ($laclac as $lac) {
                                    $lacf = jirafeau_get_link($lac); ?>
                                <tr>
                                    <td style="text-align:left;font-weigth:400;padding-left:5px;">
                                        <small><?php echo $lacf['file_name']; ?></small>
                                    </td>
                                    <td style="width:30px;padding-right:5px;text-align:center;">
                                        <a href="f.php?h=<?php echo $lac ?>&amp;d=1" target="_BLANK"><img src="img/download.svg" style="width:25px;border:none;" title="<?php echo t('DL') . ' ' . $lacf['file_name'] ?>" alt="<?php echo t('DL') . ' ' . $lacf['file_name'] ?>" onmouseover="javascript: this.src='img/download_activ.svg'" onmouseout="javascript: this.src='img/download.svg'" /></a>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="2" class="TOS">
                        <small><?php echo t('USING_SERVICE') ?> <a href="tos.php" target="_blank" id="TOS" rel="noopener noreferrer"><?php echo t('TOS') ?></a>.</small>
                    </td>
                </tr>

                <?php if ($link['onetime'] == 'O') { ?>
                <tr>
                    <td colspan="2" id="self_destruct">
                        <?php echo t('AUTO_DESTRUCT') ?>
                    </td>
                </tr>
                <?php } ?>
            </table>
        </fieldset>
        </form>
    </div>
    <?php require(JIRAFEAU_ROOT.'lib/template/footer.php');
    exit;
}

header('HTTP/1.0 200 OK');
header('Content-Length: ' . $link['file_size']);
if ($esgrupo) {
    header('Content-Disposition: attachment; filename="' . jirafeau_zipname($link_name) . '"');
    header('Content-Type: application/zip');
} else {
    if (!jirafeau_is_viewable($link['mime_type']) || !$cfg['preview'] || $do_download) {
        header('Content-Disposition: attachment; filename="' . $link['file_name'] . '"');
    } else {
        header('Content-Disposition: filename="' . $link['file_name'] . '"');
    }
    header('Content-Type: ' . $link['mime_type']);
}
if ($cfg['file_hash'] == "md5") {
    header('Content-MD5: ' . hex_to_base64($link['hash']));
}
if ($cfg['litespeed_workaround']) {
    // Work around that LiteSpeed truncates large files.
    // See https://www.litespeedtech.com/support/wiki/doku.php/litespeed_wiki:config:internal-redirect
    if ($_GET['litespeed_workaround'] == 'phase2') {
        $file_web_path = preg_replace('#^' . $_SERVER['DOCUMENT_ROOT'] . '#', '', VAR_FILES);
        header('X-LiteSpeed-Location: ' . $file_web_path . $p . $link['hash']);
    } else {
        // Since Content-Type isn't forwarded by LiteSpeed, first
        // redirect to the same URL but append the file name.
        header('Location: ' . $_SERVER['PHP_SELF'] . '/' . $link['file_name'] . '?' .
               $_SERVER['QUERY_STRING'] . '&litespeed_workaround=phase2');
    }
}
/* Read encrypted file. */
elseif ($link['crypted']) {
    /* Init module */
    $m = mcrypt_module_open('rijndael-256', '', 'ofb', '');
    /* Extract key and iv. */
    $hash_key = md5($crypt_key);
    $iv = jirafeau_crypt_create_iv($hash_key, mcrypt_enc_get_iv_size($m));
    /* Init module. */
    mcrypt_generic_init($m, $hash_key, $iv);
    /* Decrypt file. */
    $r = fopen(VAR_FILES . $p . $link['hash'], 'r');
    while (!feof($r)) {
        $dec = mdecrypt_generic($m, fread($r, 1024));
        print $dec;
    }
    fclose($r);
    /* Cleanup. */
    mcrypt_generic_deinit($m);
    mcrypt_module_close($m);
}
/* Read file. */
else {
    if ($esgrupo) {
        $fi = jirafeau_get_zip($link_name, "fread");
    } else {
        $fi = VAR_FILES . $p . $link['hash'];
        $r = fopen($fi, 'r');
        while (!feof($r)) {
            print fread($r, 1024);
        }
        fclose($r);
    }
}

if ($link['onetime'] == 'O') {
    jirafeau_delete_link($link_name);
}
exit;

?>
