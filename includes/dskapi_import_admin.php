<?php 
    if(array_key_exists('dskapi_hidden', $_POST) && $_POST['dskapi_hidden'] == 'Y') {
        if (array_key_exists('dskapi_status', $_POST)){
            $dskapi_status = $_POST['dskapi_status'];
        }else{
            $dskapi_status = '';
        }
        update_option('dskapi_status', $dskapi_status);
        
        if (array_key_exists('dskapi_cid', $_POST)){
            $dskapi_cid = $_POST['dskapi_cid'];
        }else{
            $dskapi_cid = '';
        }
        update_option('dskapi_cid', $dskapi_cid);
        
        if (array_key_exists('dskapi_reklama', $_POST)){
            $dskapi_reklama = $_POST['dskapi_reklama'];
        }else{
            $dskapi_reklama = '';
        }
        update_option('dskapi_reklama', $dskapi_reklama);
        ?>
        <div class="updated"><p><strong><?php echo 'Настройките са записани успешно.'; ?></strong></p></div>
        <?php
    } else {
        $dskapi_status = get_option('dskapi_status');
        $dskapi_cid = get_option('dskapi_cid');
        $dskapi_reklama = get_option('dskapi_reklama');
    }
    
?>
<div class="wrap">
    <h2>DSK Credit API - всички настройки на модула</h2>
    <form name="dskapi_form" method="post" enctype="multipart/form-data" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">
        <input type="hidden" name="dskapi_hidden" value="Y">
        
        <h4>Системни настройки</h4>
        <table cellspacing="4" cellpadding="4" border="0" width="900px">
            <tr>
                <td width="300px" style="vertical-align:top;">
                    DSK Credit API покупки на Кредит
                </td>
                <td width="600px;" style="vertical-align:top;">
                    <input type="checkbox" class="checkbox" id="dskapi_status" name="dskapi_status" <?php if ($dskapi_status == 'on') {echo 'checked';} ?> />
                    <span style="font-size:80%;">Дава възможност на Вашите клиенти да закупуват стока на изплащане с DSK Credit API.</span>
                </td>
            </tr>
            <tr>
                <td width="300px" style="vertical-align:top;">
                    Уникален идентификатор на магазина
                </td>
                <td width="600px;" style="vertical-align:top;">
                    <input type="text" name="dskapi_cid" value="<?php echo $dskapi_cid; ?>" size="36" style="width:300px;"><br />
                    <span style="font-size:80%;">Уникален идентификатор на магазина в системата на DSK Credit API.</span>
                </td>
            </tr>
            <tr>
                <td width="300px" style="vertical-align:top;">
                    Визуализиране на реклама
                </td>
                <td width="600px;" style="vertical-align:top;">
                    <input type="checkbox" class="checkbox" id="dskapi_reklama" name="dskapi_reklama" <?php if ($dskapi_reklama == 'on') {echo 'checked';} ?> />
                    <span style="font-size:80%;">Можете да включвате или изключвате показването на реклама в началната страница на магазина.</span>
                </td>
            </tr>
        </table>
        <hr />
        <p class="submit">
            <input type="submit" name="Submit" value="Запиши промените" />
        </p>
    </form>
</div>
