<?php
/*
 *  Jirafeau, your web file repository
 *  Copyright (C) 2015  Jerome Jutteau <jerome@jutteau.fr>
 *  Copyright (C) 2015  Nicola Spanti (RyDroid) <dev@nicola-spanti.info>
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

header('Content-Type: text/javascript');
define('JIRAFEAU_ROOT', dirname(__FILE__) . '/../');

require(JIRAFEAU_ROOT . 'lib/settings.php');
require(JIRAFEAU_ROOT . 'lib/functions.php');
require(JIRAFEAU_ROOT . 'lib/lang.php');
?>
// @license magnet:?xt=urn:btih:0b31508aeb0634b347b8270c7bee4d411b5d4109&dn=agpl-3.0.txt AGPL-v3-or-Later
var web_root = "<?php echo $cfg['web_root']; ?>";

var lang_array = <?php echo json_lang_generator(null); ?>;
var lang_array_fallback = <?php echo json_lang_generator("en"); ?>;
var reti1 = '';
var reti2 = '';
var reti3 = '';
var reti4 = '';

function translate (expr) {
    if (lang_array.hasOwnProperty(expr)) {
        var e = lang_array[expr];
        if (!isEmpty(e))
            return e;
    }
    if (lang_array_fallback.hasOwnProperty(expr)) {
        var e = lang_array_fallback[expr];
        if (!isEmpty(e))
            return e;
    }
    return "FIXME: " + expr;
}

function isEmpty(str) {
    return (!str || 0 === str.length);
}

// Extend date object with format method
Date.prototype.format = function(format) {
    format = format || 'YYYY-MM-DD hh:mm';

    var zeropad = function(number, length) {
        number = number.toString();
        length = length || 2;
        while(number.length < length)
            number = '0' + number;
        return number;
    },
    formats = {
        YYYY: this.getFullYear(),
        MM: zeropad(this.getMonth() + 1),
        DD: zeropad(this.getDate()),
        hh: zeropad(this.getHours()),
        mm: zeropad(this.getMinutes()),
        O: (function() {
            localDate = new Date;
            sign = (localDate.getTimezoneOffset() > 0) ? '-' : '+';
            offset = Math.abs(localDate.getTimezoneOffset());
            hours = zeropad(Math.floor(offset / 60));
            minutes = zeropad(offset % 60);
            return sign + hours + ":" + minutes;
        })()
    },
    pattern = '(' + Object.keys(formats).join(')|(') + ')';

    return format.replace(new RegExp(pattern, 'g'), function(match) {
        return formats[match];
    });
};

function dateFromUtcString(datestring) {
    // matches »YYYY-MM-DD hh:mm«
    var m = datestring.match(/(\d+)-(\d+)-(\d+)\s+(\d+):(\d+)/);
    return new Date(Date.UTC(+m[1], +m[2] - 1, +m[3], +m[4], +m[5], 0));
}

function dateFromUtcTimestamp(datetimestamp) {
    return new Date(parseInt(datetimestamp) * 1000)
}

function dateToUtcString(datelocal) {
    return new Date(
        datelocal.getUTCFullYear(),
        datelocal.getUTCMonth(),
        datelocal.getUTCDate(),
        datelocal.getUTCHours(),
        datelocal.getUTCMinutes(),
        datelocal.getUTCSeconds()
    ).format();
}

function dateToUtcTimestamp(datelocal) {
    return (Date.UTC(
        datelocal.getUTCFullYear(),
        datelocal.getUTCMonth(),
        datelocal.getUTCDate(),
        datelocal.getUTCHours(),
        datelocal.getUTCMinutes(),
        datelocal.getUTCSeconds()
    ) / 1000);
}

function convertAllDatetimeFields() {
    datefields = document.getElementsByClassName('datetime')
    for(var i=0; i<datefields.length; i++) {
        dateUTC = datefields[i].getAttribute('data-datetime');
        datefields[i].setAttribute('title', dateUTC + ' (GMT)');
        datefields[i].innerHTML = dateFromUtcString(dateUTC).format('YYYY-MM-DD hh:mm (GMT O)');
    }
}

function show_link (reference, delete_code, crypt_key, date)
{
    var req = new XMLHttpRequest ();
    req.addEventListener ("error", XHRErrorHandler, false);
    req.addEventListener ("abort", XHRErrorHandler, false);
    req.onreadystatechange = function () {
        if (req.readyState == 4) {
            if (req.status == 200) {
                var res = req.responseText;

                // if response starts with "Error" then show a failure
                if (/^Error/.test(res)) {
                    pop_failure (res);
                    return;
                }
                // Upload finished
                document.getElementById('uploading').style.display = 'none';
                document.getElementById('upload').style.display = 'none';
                document.getElementById('upload_finished').style.display = '';
                document.title = "100% - <?php echo empty($cfg['title']) ? 'Jirafeau' : $cfg['title']; ?>";
                // Validity date
                if (isEmpty(date)) {
                    document.getElementById('date').style.display = 'none';
                } else {
                    document.getElementById('date').innerHTML = '<span class="datetime" title="'
                        + date.format('DD-MM-YYYY hh:mm') + '">'
                        + date.format('DD-MM-YYYY hh:mm')
                        + '</span>';
                    document.getElementById('date').style.display = '';
                }
            } else {
                pop_failure ("<?php echo t("ERR_OCC"); ?>");
            }
        }
    }
    // Upload finished
    document.getElementById('uploading').style.display = 'none';
    document.getElementById('upload').style.display = 'none';
    document.getElementById('upload_finished').style.display = '';
    document.title = "100% - <?php echo empty($cfg['title']) ? 'Jirafeau' : $cfg['title']; ?>";
    // Validity date
    if (isEmpty(date)) {
        document.getElementById('date').style.display = 'none';
    } else {
        document.getElementById('date').innerHTML = '<span class="datetime" title="'
            + date.format('DD-MM-YYYY hh:mm') + '">'
            + date.format('DD-MM-YYYY hh:mm')
            + '</span>';
        document.getElementById('date').style.display = '';
    }


    // Send mails
    req.open ("POST", 'lib/sendmail.php' , true);

    var form = new FormData();
    form.append ("destinos", document.getElementById('destMail').value);
    form.append ("nombre", document.getElementById('tuNombre').value);
    form.append ("email", document.getElementById('tuMail').value);
    form.append ("mensaje", document.getElementById('tuMensaje').value);
    form.append ("enlace", reference);
    form.append ("codigo_borra", delete_code);
    if (crypt_key.length > 0) {
        form.append ("encriptacion", crypt_key);
    }
    form.append ("fecha", date.format('DD-MM-YYYY hh:mm'));

    req.send (form);

}

function show_upload_progression (percentage, speed, time_left)
{
    document.getElementById('uploaded_percentage').innerHTML = percentage;
    document.getElementById('uploaded_speed').innerHTML = speed;
    document.getElementById('uploaded_time').innerHTML = time_left;
    document.title = percentage + " - <?php echo empty($cfg['title']) ? 'Jirafeau' : $cfg['title']; ?>";
}

function hide_upload_progression ()
{
    document.getElementById('uploaded_percentage').style.display = 'none';
    document.getElementById('uploaded_speed').style.display = 'none';
    document.getElementById('uploaded_time').style.display = 'none';
    document.title = "<?php echo empty($cfg['title']) ? 'Jirafeau' : $cfg['title']; ?>";
}

function upload_progress (e)
{
    if (e == undefined || e == null || !e.lengthComputable)
        return;

    // Init time estimation if needed
    if (upload_time_estimation_total_size == 0)
        upload_time_estimation_total_size = e.total;

    // Compute percentage
    var p = Math.round (e.loaded * 100 / e.total);
    var p_str = ' ';
    if (p != 100)
        p_str = p.toString() + '%';
    // Update estimation speed
    upload_time_estimation_add(e.loaded);
    // Get speed string
    var speed_str = upload_time_estimation_speed_string();
    speed_str = upload_speed_refresh_limiter(speed_str);
    // Get time string
    var time_str = chrono_update(upload_time_estimation_time());

    show_upload_progression (p_str, speed_str, time_str);
}

function control_selected_file_size(max_size, error_str)
{
    var aFiles = document.getElementById("file_select").files;
    f_size = 0;
    for (let cont = 0; cont < aFiles.length; cont++) {
        f_size += aFiles[cont].size;
    }
    <!-- f_size = document.getElementById('file_select').files[0].size; -->
    if (max_size > 0 && f_size > max_size * 1024 * 1024)
    {
        pop_failure(error_str);
        document.getElementById('send').style.display = 'none';
    }
    else
    {
        // add class to restyle upload form in next step
        document.getElementById('upload').setAttribute('class', 'file-selected');
        // display options
        document.getElementById('options').style.display = 'block';
        document.getElementById('send').style.display = 'block';
        document.getElementById('error_pop').style.display = 'none';
        document.getElementById('file_select').style.display = 'none';
        document.getElementById('losfiles').style.display = 'none';
        document.getElementById('email_table').style.display = '';
        document.getElementById('upload').firstElementChild.firstElementChild.textContent="Rellena el formulario"
        document.getElementById('send').focus();
    }
}

function XHRErrorHandler(e)
{
    var text = "${e.type}: ${e.loaded} bytes transferred"
    console.log(text)
}

function pop_failure (e)
{
    var text = "<p>An error occured";
    if (typeof e !== 'undefined')
        text += ": " + e;
    text += "</p>";
    document.getElementById('error_pop').innerHTML = e;

    document.getElementById('uploading').style.display = 'none';
    document.getElementById('error_pop').style.display = '';
    document.getElementById('upload').style.display = '';
    document.getElementById('send').style.display = '';
}

function add_time_string_to_date(d, time)
{
    if(typeof(d) != 'object' || !(d instanceof Date))
    {
        return false;
    }

    if (time == 'minute')
    {
        d.setSeconds (d.getSeconds() + 60);
        return true;
    }
    if (time == 'hour')
    {
        d.setSeconds (d.getSeconds() + 3600);
        return true;
    }
    if (time == 'day')
    {
        d.setSeconds (d.getSeconds() + 86400);
        return true;
    }
    if (time == 'week')
    {
        d.setSeconds (d.getSeconds() + 604800);
        return true;
    }
    if (time == 'month')
    {
		d.setSeconds (d.getSeconds() + 2592000);
        return true;
    }
    if (time == 'quarter')
    {
		d.setSeconds (d.getSeconds() + 7776000);
        return true;
    }
    if (time == 'year')
    {
		d.setSeconds (d.getSeconds() + 31536000);
        return true;
    }
    return false;
}

var classic_global_max_size = 0;
var classic_global_size = 0;
var classic_global_ff = 0;

function classic_upload (max_size, file, time, password, one_time, upload_password, grupo, leni)
{
    classic_global_max_size = max_size;
    classic_global_size += file.size;
    classic_global_ff = eval(leni - 1);

    // Delay time estimation init as we can't have file size
    upload_time_estimation_init(0);

    var req = new XMLHttpRequest ();
    req.upload.addEventListener ("progress", upload_progress, false);
    req.addEventListener ("error", XHRErrorHandler, false);
    req.addEventListener ("abort", XHRErrorHandler, false);
    req.onreadystatechange = function ()
    {
        if (req.readyState == 4 && req.status == 200)
        {
            var res = req.responseText;

            // if response starts with "Error" then show a failure
            if (/^Error/.test(res))
            {
                pop_failure (res);
                return;
            }

            res = res.split ("\n");
            var expiryDate = '';
            if (time != 'none')
            {
                // convert time (local time + selected expiry date)
                var localDatetime = new Date();
                if(!add_time_string_to_date(localDatetime, time))
                {
                    pop_failure ('Error: Date can not be parsed');
                    return;
                }
                expiryDate = localDatetime;
            }
            if (/^Continuando/.test(res)) {
                upload(classic_global_max_size, classic_global_ff);
            } else {
                show_link (res[0], res[1], res[2], expiryDate);
            }
        }
        else
        {
            pop_failure ("<?php echo t("ERR_OCC"); ?>");
        }
    }
    req.open ("POST", 'script.php' , true);

    var form = new FormData();
    form.append ("file", file);
    form.append ("grupo", grupo);
    if (time)
        form.append ("time", time);
    if (password)
        form.append ("key", password);
    if (one_time)
        form.append ("one_time_download", '1');
    if (upload_password.length > 0)
        form.append ("upload_password", upload_password);
    if (classic_global_ff == 0) {
        form.append ("end", classic_global_size);
    } else {
        form.append ("end", 'NO');
    }
    req.send (form);
}

function check_html5_file_api ()
{
    return window.File && window.FileReader && window.FileList && window.Blob;
}

var async_global_transfered = 0;
var async_global_file;
var async_global_ref = '';
var async_global_max_size = 0;
var async_global_time;
var async_global_ff = 0;
var async_global_f_size = 0;
var async_global_grupo;
var async_global_transfering = 0;
var async_global_last_code;

function async_upload_start (max_size, file, time, password, one_time, upload_password, grupo, ff)
{
    async_global_transfered = 0;
    async_global_file = file;
    async_global_max_size = max_size;
    async_global_time = time;
    async_global_grupo = grupo;
    async_global_ff = ff;
    async_global_f_size += async_global_file.size;

    var req = new XMLHttpRequest ();
    req.addEventListener ("error", XHRErrorHandler, false);
    req.addEventListener ("abort", XHRErrorHandler, false);
    req.onreadystatechange = function ()
    {
        if (req.readyState == 4 && req.status == 200)
        {
            var res = req.responseText;

            if (/^Error/.test(res))
            {
                pop_failure (res);
                return;
            }

            res = res.split ("\n");
            async_global_ref = res[0];
            var code = res[1];
            async_upload_push (code);
        }
    }
    req.open ("POST", 'script.php?init_async' , true);

    var form = new FormData();
    form.append ("filename", async_global_file.name);
    form.append ("type", async_global_file.type);
    form.append ("grupo", async_global_grupo);
    if (time)
        form.append ("time", time);
    if (password)
        form.append ("key", password);
    if (one_time)
        form.append ("one_time_download", '1');
    if (upload_password.length > 0)
        form.append ("upload_password", upload_password);

    // Start time estimation
    upload_time_estimation_init(async_global_file.size);

    req.send (form);
}

function async_upload_progress (e)
{
    if (e == undefined || e == null || !e.lengthComputable && async_global_file.size != 0)
        return;

    // Compute percentage
    var p = Math.round ((e.loaded + async_global_transfered) * 100 / (async_global_file.size));
    var p_str = ' ';
    if (p != 100)
        p_str = p.toString() + '%';
    // Update estimation speed
    upload_time_estimation_add(e.loaded + async_global_transfered);
    // Get speed string
    var speed_str = upload_time_estimation_speed_string();
    speed_str = upload_speed_refresh_limiter(speed_str);
    // Get time string
    var time_str = chrono_update(upload_time_estimation_time());

    show_upload_progression (p_str, speed_str, time_str);
}

function async_upload_push (code)
{
    async_global_last_code = code;
    if (async_global_transfered == async_global_file.size)
    {
        hide_upload_progression ();
        async_upload_end (code);
        return;
    }
    var req = new XMLHttpRequest ();
    req.upload.addEventListener ("progress", async_upload_progress, false);
    req.addEventListener ("error", XHRErrorHandler, false);
    req.addEventListener ("abort", XHRErrorHandler, false);
    req.onreadystatechange = function ()
    {
        if (req.readyState == 4)
        {
            if (req.status == 200)
            {
                var res = req.responseText;

                // This error may be triggered when Jirafeau does not receive any file in POST.
                // This may be due to bad php configuration where post_max_size is too low
                // comparing to upload_max_filesize. Let's retry with lower file size.
                if (res === "Error 23")
                {
                    async_global_max_size = Math.max(1, async_global_max_size - 500);
                    async_upload_push (async_global_last_code);
                    return;
                }
                else if (/^Error/.test(res))
                {
                    pop_failure (res);
                    return;
                }

                res = res.split ("\n");
                var code = res[0]
                async_global_transfered = async_global_transfering;
                async_upload_push (code);
                return;
            }
            else
            {
                if (req.status == 413) // Request Entity Too Large
                {
                    // lower async_global_max_size and retry
                    async_global_max_size = Math.max(1, parseInt (async_global_max_size * 0.8));
                }
                async_upload_push (async_global_last_code);
                return;
            }
        }
    }
    req.open ("POST", 'script.php?push_async' , true);

    var start = async_global_transfered;
    var end = start + async_global_max_size;
    if (end >= async_global_file.size)
        end = async_global_file.size;
    var blob = async_global_file.slice (start, end);
    async_global_transfering = end;

    var form = new FormData();
    form.append ("ref", async_global_ref);
    form.append ("data", blob);
    form.append ("code", code);
    req.send (form);
}

function async_upload_end (code)
{
    var req = new XMLHttpRequest ();
    req.addEventListener ("error", XHRErrorHandler, false);
    req.addEventListener ("abort", XHRErrorHandler, false);
    req.onreadystatechange = function ()
    {
        if (req.readyState == 4 && req.status == 200)
        {
            var res = req.responseText;

            if (/^Error/.test(res))
            {
                pop_failure (res);
                return;
            }
            res = res.split ("\n");
            var expiryDate = '';
            if (async_global_time != 'none')
            {
                // convert time (local time + selected expiry date)
                var localDatetime = new Date();
                if(!add_time_string_to_date(localDatetime, async_global_time)) {
                    pop_failure ('Error: Date can not be parsed');
                    return;
                }
                expiryDate = localDatetime;
            }
            if (res != "NO") {
                reti1 = res[0];
                reti2 = res[1];
                reti3 = res[2];
                reti4 = expiryDate;
                show_link(reti1, reti2, reti3, reti4);
            } else {
                upload(async_global_max_size, async_global_ff);
            }
        }
    }
    req.open ("POST", 'script.php?end_async' , true);

    var form = new FormData();
    form.append ("ref", async_global_ref);
    form.append ("code", code);
    form.append ("grupo", async_global_grupo);
    if (async_global_ff == 0) {
        form.append ("end", async_global_f_size);
    } else {
        form.append ("end", 'NO');
    }
    req.send (form);
}

function upload (max_size, lenn)
{
    var one_time_checkbox = document.getElementById('one_time_download');
    var one_time = one_time_checkbox !== null ? one_time_checkbox.checked : false;
    var grupo = document.getElementById('grupo').value;
    var f_size = 0;
    // var f_files = document.getElementById('file_select').files.length;
    // var f_f = document.getElementById('file_select').files.length;
    // f_f = eval(f_f - 1);
    // for (let i = 0; i < f_files; i++)
    // {
    // f_size += document.getElementById('file_select').files[i].size;
    var leni = eval(lenn - 1);
    if (check_html5_file_api ())
    {
        async_upload_start
        (
            max_size,
            document.getElementById('file_select').files[leni],
            document.getElementById('select_time').value,
            document.getElementById('input_key').value,
            one_time,
            document.getElementById('upload_password').value,
            grupo,
            leni
        );
    }
    else
    {
        classic_upload
        (
            max_size,
            document.getElementById('file_select').files[leni],
            document.getElementById('select_time').value,
            document.getElementById('input_key').value,
            one_time,
            document.getElementById('upload_password').value,
            grupo,
            leni
        );
    }
    // }
}

var upload_time_estimation_total_size = 42;
var upload_time_estimation_transfered_size = 42;
var upload_time_estimation_transfered_date = 42;
var upload_time_estimation_moving_average_speed = 42;

function upload_time_estimation_init(total_size)
{
    upload_time_estimation_total_size = total_size;
    upload_time_estimation_transfered_size = 0;
    upload_time_estimation_moving_average_speed = 0;
    var d = new Date();
    upload_time_estimation_transfered_date = d.getTime();
}

function upload_time_estimation_add(total_transfered_size)
{
    // Let's compute the current speed
    var d = new Date();
    var speed = upload_time_estimation_moving_average_speed;
    if (d.getTime() - upload_time_estimation_transfered_date != 0)
        speed = (total_transfered_size - upload_time_estimation_transfered_size)
                / (d.getTime() - upload_time_estimation_transfered_date);
    // Let's compute moving average speed on 30 values
    var m = (upload_time_estimation_moving_average_speed * 29 + speed) / 30;
    // Update global values
    upload_time_estimation_transfered_size = total_transfered_size;
    upload_time_estimation_transfered_date = d.getTime();
    upload_time_estimation_moving_average_speed = m;
}

function upload_time_estimation_speed_string()
{
    // speed ms -> s
    var s = upload_time_estimation_moving_average_speed * 1000;
    var res = 0;
    var scale = '';
    if (s <= 1000)
    {
        res = s.toString();
        scale = "B/s";
    }
    else if (s < 1000000)
    {
        res = Math.floor(s/100) / 10;
        scale = "KB/s";
    }
    else
    {
        res = Math.floor(s/100000) / 10;
        scale = "MB/s";
    }
    if (res == 0)
        return '';
    return res.toString() + ' ' + scale;
}

function milliseconds_to_time_string (milliseconds)
{
    function numberEnding (number) {
        return (number > 1) ? translate ('PLURAL_ENDING') : '';
    }

    var temp = Math.floor(milliseconds / 1000);
    var years = Math.floor(temp / 31536000);
    if (years) {
        return years + ' ' + translate ('YEAR') + numberEnding(years);
    }
    var days = Math.floor((temp %= 31536000) / 86400);
    if (days) {
        return days + ' ' + translate ('DAY') + numberEnding(days);
    }
    var hours = Math.floor((temp %= 86400) / 3600);
    if (hours) {
        return hours + ' ' + translate ('HOUR') + numberEnding(hours);
    }
    var minutes = Math.floor((temp %= 3600) / 60);
    if (minutes) {
        return minutes + ' ' + translate ('MINUTE') + numberEnding(minutes);
    }
    var seconds = temp % 60;
    if (seconds) {
        return seconds + ' ' + translate ('SECOND') + numberEnding(seconds);
    }
    return translate ('LESS_1_SEC');
}

function upload_time_estimation_time()
{
    // Estimate remaining time
    if (upload_time_estimation_moving_average_speed == 0)
        return 0;
    return (upload_time_estimation_total_size - upload_time_estimation_transfered_size)
            / upload_time_estimation_moving_average_speed;
}

var chrono_last_update = 0;
var chrono_time_ms = 0;
var chrono_time_ms_last_update = 0;
function chrono_update(time_ms)
{
    var d = new Date();
    var chrono = 0;
    // Don't update too often
    if (d.getTime() - chrono_last_update < 3000 &&
        chrono_time_ms_last_update > 0)
        chrono = chrono_time_ms;
    else
    {
        chrono_last_update = d.getTime();
        chrono_time_ms = time_ms;
        chrono = time_ms;
        chrono_time_ms_last_update = d.getTime();
    }

    // Adjust chrono for smooth estimation
    chrono = chrono - (d.getTime() - chrono_time_ms_last_update);

    // Let's update chronometer
    var time_str = '';
    if (chrono > 0)
        time_str = milliseconds_to_time_string (chrono);
    return time_str;
}

var upload_speed_refresh_limiter_last_update = 0;
var upload_speed_refresh_limiter_last_value = '';
function upload_speed_refresh_limiter(speed_str)
{
    var d = new Date();
    if (d.getTime() - upload_speed_refresh_limiter_last_update > 1500)
    {
        upload_speed_refresh_limiter_last_value = speed_str;
        upload_speed_refresh_limiter_last_update = d.getTime();
    }
    return upload_speed_refresh_limiter_last_value;
}

// document.ready()
document.addEventListener('DOMContentLoaded', function(event) {
    // Search for all datetime fields and convert the time to local timezone
    convertAllDatetimeFields();
});

// Add copy event listeners
function copyLinkToClipboard(link_id) {
    var focus = document.activeElement;
    var e = document.getElementById(link_id);

    var tmp = document.createElement("textarea");
    document.body.appendChild(tmp);
    tmp.textContent = e.href;
    tmp.focus();
    tmp.setSelectionRange(0, tmp.value.length);
    document.execCommand("copy");
    document.body.removeChild(tmp);

    focus.focus();
}

function addCopyListener(button_id, link_id) {
    if(document.getElementById(button_id)){
        document.getElementById(button_id)
            .addEventListener("click", function() {
                copyLinkToClipboard(link_id);});
    }
}
// @license-end
