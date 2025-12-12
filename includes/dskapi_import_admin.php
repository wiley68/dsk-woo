<?php

/**
 * DSK API Payment Gateway - Admin Settings Page
 *
 * Handles the plugin's admin settings page in WordPress dashboard.
 * Allows administrators to configure:
 * - Plugin enable/disable status
 * - Calculator ID (store identifier)
 * - Front page advertisement toggle
 * - Button margin/gap settings
 *
 * @package DSK_POS_Loans
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Process form submission and save settings.
 *
 * Handles POST data when form is submitted:
 * - dskapi_status: Enable/disable plugin
 * - dskapi_cid: Store calculator ID
 * - dskapi_reklama: Advertisement toggle
 * - dskapi_gap: Button top margin in pixels
 */
if (array_key_exists('dskapi_hidden', $_POST) && $_POST['dskapi_hidden'] == 'Y') {
    // Process plugin status (on/off)
    if (array_key_exists('dskapi_status', $_POST)) {
        $dskapi_status = sanitize_text_field($_POST['dskapi_status']);
    } else {
        $dskapi_status = '';
    }
    update_option('dskapi_status', $dskapi_status);

    // Process calculator ID
    if (array_key_exists('dskapi_cid', $_POST)) {
        $dskapi_cid = sanitize_text_field($_POST['dskapi_cid']);
    } else {
        $dskapi_cid = '';
    }
    update_option('dskapi_cid', $dskapi_cid);

    // Process advertisement toggle
    if (array_key_exists('dskapi_reklama', $_POST)) {
        $dskapi_reklama = sanitize_text_field($_POST['dskapi_reklama']);
    } else {
        $dskapi_reklama = '';
    }
    update_option('dskapi_reklama', $dskapi_reklama);

    // Process button gap (positive integer only)
    if (array_key_exists('dskapi_gap', $_POST)) {
        $dskapi_gap = absint($_POST['dskapi_gap']);
    } else {
        $dskapi_gap = 0;
    }
    update_option('dskapi_gap', $dskapi_gap);
?>
    <div class="updated">
        <p><strong><?php echo 'Настройките са записани успешно.'; ?></strong></p>
    </div>
<?php
} else {
    /**
     * Load existing settings from database.
     *
     * Retrieves current option values when form is not being submitted.
     */
    $dskapi_status = get_option('dskapi_status');
    $dskapi_cid = get_option('dskapi_cid');
    $dskapi_reklama = get_option('dskapi_reklama');
    $dskapi_gap = get_option('dskapi_gap', 0);
}

?>
<!-- Admin Settings Form -->
<div class="wrap">
    <h2>DSK Credit API - всички настройки на модула</h2>
    <form name="dskapi_form" method="post" enctype="multipart/form-data" action="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>">
        <!-- Hidden field to identify form submission -->
        <input type="hidden" name="dskapi_hidden" value="Y">

        <h4>Системни настройки</h4>
        <table cellspacing="4" cellpadding="4" border="0" width="900px">
            <!-- Plugin Enable/Disable -->
            <tr>
                <td width="300px" style="vertical-align:top;">
                    DSK Credit API покупки на Кредит
                </td>
                <td width="600px;" style="vertical-align:top;">
                    <input type="checkbox" class="checkbox" id="dskapi_status" name="dskapi_status" <?php if ($dskapi_status == 'on') {
                                                                                                        echo 'checked';
                                                                                                    } ?> />
                    <span style="font-size:80%;">Дава възможност на Вашите клиенти да закупуват стока на изплащане с DSK Credit API.</span>
                </td>
            </tr>

            <!-- Calculator/Store ID -->
            <tr>
                <td width="300px" style="vertical-align:top;">
                    Уникален идентификатор на магазина
                </td>
                <td width="600px;" style="vertical-align:top;">
                    <input type="text" name="dskapi_cid" value="<?php echo esc_attr($dskapi_cid); ?>" size="36" style="width:300px;"><br />
                    <span style="font-size:80%;">Уникален идентификатор на магазина в системата на DSK Credit API.</span>
                </td>
            </tr>

            <!-- Advertisement Toggle -->
            <tr>
                <td width="300px" style="vertical-align:top;">
                    Визуализиране на реклама
                </td>
                <td width="600px;" style="vertical-align:top;">
                    <input type="checkbox" class="checkbox" id="dskapi_reklama" name="dskapi_reklama" <?php if ($dskapi_reklama == 'on') {
                                                                                                            echo 'checked';
                                                                                                        } ?> />
                    <span style="font-size:80%;">Можете да включвате или изключвате показването на реклама в началната страница на магазина.</span>
                </td>
            </tr>

            <!-- Button Top Margin -->
            <tr>
                <td width="300px" style="vertical-align:top;">
                    Свободно място над бутона
                </td>
                <td width="600px;" style="vertical-align:top;">
                    <input type="number" name="dskapi_gap" value="<?php echo absint($dskapi_gap); ?>" min="0" step="1" style="width:100px;"><br />
                    <span style="font-size:80%;">Свободно място над бутона в px.</span>
                </td>
            </tr>
        </table>
        <hr />

        <!-- Submit Button -->
        <p class="submit">
            <input type="submit" name="Submit" class="button button-primary" value="Запиши промените" />
        </p>
    </form>
</div>