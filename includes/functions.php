<?php
    /** load plugin class */
    function dskapi_load_class_plugin(){
        if (!class_exists('WC_Payment_Gateway'))
            return;
        include(DSKAPI_INCLUDES_DIR . '/class-gateway.php');
    }
    
    /** add payment gateway */
    function add_dskapi_gateway_class($gateways) {
      $gateways[] = 'Dskapi_Payment_Gateway';
      return $gateways;
    }
    
    /** do output buffer */
    function dskapi_do_output_buffer() {
        ob_start();
    }
    
    /** load admin menu */
    function dskapi_admin_options() {
        include('dskapi_import_admin.php');
    }
    
    /** add origins */
    function dskapi_add_allowed_origins( $origins ) {
        $origins[] = DSKAPI_LIVEURL;
        return $origins;
    }
    
    /** add order column dskapi status */
    function dskapi_add_order_column_status( $columns ) {
        $dskapi_status_columns = ( is_array( $columns ) ) ? $columns : array();
        unset( $dskapi_status_columns[ 'order_actions' ] );
        $dskapi_status_columns['dskapi_status_columnt'] = 'DSK Credit API Статус';
        $dskapi_status_columns[ 'order_actions' ] = $columns[ 'order_actions' ];
        return $dskapi_status_columns;
    }
    
    /** add order column dskapi status hpos */
    function dskapi_add_order_column_status_hpos( $columns ) {
        $dskapi_reordered_columns = array();
        foreach( $columns as $key => $column){
            $dskapi_reordered_columns[$key] = $column;
            if( $key ===  'order_status' ){
                $dskapi_reordered_columns['dskapi_status_columnt'] = 'DSK Credit API Статус';
            }
        }
        return $dskapi_reordered_columns;
    }
    
    /** add order column dskapi status values */
    function dskapi_add_order_column_status_values( $column ) {
        global $post;
        $data = get_post_meta( $post->ID );
        if ( $column == 'dskapi_status_columnt' ) {
            $dskapi_status = '';
            if (file_exists(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json')){
                $dskapi_orderdata = file_get_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json');
                $dskapi_orderdata_all = json_decode($dskapi_orderdata, true);
                foreach ($dskapi_orderdata_all as $key => $value){
                    if ($dskapi_orderdata_all[$key]['order_id'] == $post->ID){
                        switch ($dskapi_orderdata_all[$key]['order_status']) {
                            case 0:
                                $dskapi_status = "Създадена Апликация";
                            break;
                            case 1:
                                $dskapi_status = "Избрана финансова схема";
                            break;
                            case 2:
                                $dskapi_status = "Попълнена Апликация";
                            break;
                            case 3:
                                $dskapi_status = "Изпратен Банка";
                            break;
                            case 4:
                                $dskapi_status = "Неуспешен контакт с клиента";
                            break;
                            case 5:
                                $dskapi_status = "Анулирана апликация";
                            break;
                            case 6:
                                $dskapi_status = "Отказана апликация";
                            break;
                            case 7:
                                $dskapi_status = "Подписан договор";
                            break;
                            case 8:
                                $dskapi_status = "Усвоен кредит";
                            break;
                            default:
                                $dskapi_status = "Създадена Апликация";
                            break;
                        }
                    }
                }
            }
            echo ( $dskapi_status );
        }
    }
    
    /** add order column dskapi status values hpos */
    function dskapi_add_order_column_status_values_hpos( $column, $order ){
        if ( $column == 'dskapi_status_columnt') {
            $dskapi_order_id = $order->get_id();
            $dskapi_status = '';
            if (file_exists(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json')){
                $dskapi_orderdata = file_get_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json');
                $dskapi_orderdata_all = json_decode($dskapi_orderdata, true);
                foreach ($dskapi_orderdata_all as $key => $value){
                    if ($dskapi_orderdata_all[$key]['order_id'] == $dskapi_order_id){
                        switch ($dskapi_orderdata_all[$key]['order_status']) {
                            case 0:
                                $dskapi_status = "Създадена Апликация";
                            break;
                            case 1:
                                $dskapi_status = "Избрана финансова схема";
                            break;
                            case 2:
                                $dskapi_status = "Попълнена Апликация";
                            break;
                            case 3:
                                $dskapi_status = "Изпратен Банка";
                            break;
                            case 4:
                                $dskapi_status = "Неуспешен контакт с клиента";
                            break;
                            case 5:
                                $dskapi_status = "Анулирана апликация";
                            break;
                            case 6:
                                $dskapi_status = "Отказана апликация";
                            break;
                            case 7:
                                $dskapi_status = "Подписан договор";
                            break;
                            case 8:
                                $dskapi_status = "Усвоен кредит";
                            break;
                            default:
                                $dskapi_status = "Създадена поръчка";
                            break;
                        }
                    }
                }
            }
            echo ( $dskapi_status );
        }
    }
    
    function dskapi_wordpress_get_params($param = null,$null_return = null){
        if ($param){
            $value = (!empty($_POST[$param]) ? trim(esc_sql($_POST[$param])) : (!empty($_GET[$param]) ? trim(esc_sql($_GET[$param])) : $null_return ));
            return $value;
        } else {
            $params = array();
            foreach ($_POST as $key => $param) {
                $params[trim(esc_sql($key))] = (!empty($_POST[$key]) ? trim(esc_sql($_POST[$key])) :  $null_return );
            }
            foreach ($_GET as $key => $param) {
                $key = trim(esc_sql($key));
                if (!isset($params[$key])) { // if there is no key or it's a null value
                    $params[trim(esc_sql($key))] = (!empty($_GET[$key]) ? trim(esc_sql($_GET[$key])) : $null_return );
                }
            }
            return $params;
        }
    }
    
    function dskapi_add_meta() {
        //register css-s
        if ( is_front_page() ){
            wp_enqueue_style( 'dskapi_style_rek', plugin_dir_url( __FILE__ ) . '../css/dskapi_rek.css', false, DSKAPI_VERSION, 'all');
            wp_enqueue_script( 'dskapi_js_rek', plugin_dir_url( __FILE__ ) . '../js/dskapi_rek.js', false, DSKAPI_VERSION);
        }
        if ( is_product() ){
            wp_enqueue_style( 'dskapi_style_product', plugin_dir_url( __FILE__ ) . '../css/dskapi_product.css', false, DSKAPI_VERSION, 'all');
            wp_enqueue_script( 'dskapi_js_product', plugin_dir_url( __FILE__ ) . '../js/dskapi_product.js', false, DSKAPI_VERSION);
        }
    }
    
    function dskapi_reklama() {
        $o = '';
        if ( is_front_page() ) {
            $dskapi_cid = (string)get_option("dskapi_cid");
            $dskapi_reklama = (string)get_option("dskapi_reklama");
            $dskapi_status = (string)get_option("dskapi_status");
            
            if (($dskapi_reklama == "on") && ($dskapi_status == "on")){
                $dskapi_ch = curl_init();
                curl_setopt($dskapi_ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($dskapi_ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($dskapi_ch, CURLOPT_MAXREDIRS, 2);
                curl_setopt($dskapi_ch, CURLOPT_TIMEOUT, 6);
                curl_setopt($dskapi_ch, CURLOPT_URL, DSKAPI_LIVEURL . '/function/getrek.php?cid='.$dskapi_cid);
                $paramsdskapi = json_decode(curl_exec($dskapi_ch), true);
                curl_close($dskapi_ch);
            
                $useragent = $_SERVER['HTTP_USER_AGENT'];
                if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))){
                    $dskapi_deviceis = "Yes";
                }else{
                    $dskapi_deviceis = "No";
                }
                if ((!empty($paramsdskapi)) && ($paramsdskapi['dsk_status'] == 1) && ($paramsdskapi['dsk_container_status'] == 1)){ 
                    if ($dskapi_deviceis == "Yes"){
                        $o .= '<div class="dskapi_float" onclick="window.open(\'' . DSKAPI_LIVEURL . '/procedure.php\', \'_blank\');">';
                    }else{
                        $o .= '<div class="dskapi_float" onclick="DskapiChangeContainer();">';
                    }
                    $o .= '<img src="' . DSKAPI_LIVEURL . '/dist/img/dsk_logo.png" class="dskapi-my-float">';
                    $o .= '</div>';
                    $o .= '<div class="dskapi-label-container">';
                    $o .= '<div class="dskapi-label-text">';
                    $o .= '<div class="dskapi-label-text-mask">';
                    $o .= '<img src="' . $paramsdskapi['dsk_picture'] . '" class="dskapi_header">';
                    $o .= '<p class="dskapi_txt1">' . $paramsdskapi['dsk_container_txt1'] . '</p>';
                    $o .= '<p class="dskapi_txt2">' . $paramsdskapi['dsk_container_txt2'] . '</p>';
                    $o .= '<p class="dskapi-label-text-a"><a href="' . $paramsdskapi['dsk_logo_url'] . '" target="_blank" alt="За повече информация">За повече информация</a></p>';
                    $o .= '</div>';
                    $o .= '</div>';
                    $o .= '</div>';
                }
            }        
        }
        echo $o;
    } 
    
    /** vizualize credit button */
    function dskpayment_button() {
        $dskapi_status = (string)get_option("dskapi_status");
        
        if ($dskapi_status == "on") {
            $dskapi_cid = (string)get_option("dskapi_cid");
            global $product;
            global $woocommerce;
            if( version_compare( $woocommerce->version, '2.6', ">=" ) ) {
                $dskapi_product_id = $product->get_id();
                $dskapi_product_name = $product->get_name();
            }else{
                $dskapi_product_id = $product->id;
                $dskapi_product_name = $product->name;
            }
            $dskapi_price = wc_get_price_including_tax($product);
            if ($dskapi_price == 0){
                $dskapi_is_empty = true;
            }else{
                $dskapi_is_empty = false;
            }
            
            $dskapi_currency_code = get_woocommerce_currency();
            if ($dskapi_currency_code != 'EUR' && $dskapi_currency_code != 'BGN') {
                return NULL;
            }
            
            $dskapi_ch_eur = curl_init();
            curl_setopt($dskapi_ch_eur, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($dskapi_ch_eur, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($dskapi_ch_eur, CURLOPT_MAXREDIRS, 3);
            curl_setopt($dskapi_ch_eur, CURLOPT_TIMEOUT, 5);
            curl_setopt($dskapi_ch_eur, CURLOPT_URL, DSKAPI_LIVEURL . '/function/geteur.php?cid=' . $dskapi_cid);
            $paramsdskapieur = json_decode(curl_exec($dskapi_ch_eur), true);
            
            if ($paramsdskapieur == null) {
                return NULL;
            }
            
            $dskapi_eur = (int)$paramsdskapieur['dsk_eur'];
            $dskapi_sign = 'лв.';
            switch ($dskapi_eur) {
                case 0:
                    break;
                case 1:
                    if ($dskapi_currency_code == "EUR") {
                        $dskapi_price = number_format($dskapi_price * 1.95583, 2, ".", "");
                    }
                    $dskapi_sign = 'лв.';
                    break;
                case 2:
                    if ($dskapi_currency_code == "BGN") {
                        $dskapi_price = number_format($dskapi_price / 1.95583, 2, ".", "");
                    }
                    $dskapi_sign = 'евро';
                    break;
            }
            
            $dskapi_ch = curl_init();
            curl_setopt($dskapi_ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($dskapi_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($dskapi_ch, CURLOPT_MAXREDIRS, 3);
            curl_setopt($dskapi_ch, CURLOPT_TIMEOUT, 6);
            curl_setopt($dskapi_ch, CURLOPT_URL, DSKAPI_LIVEURL . '/function/getproduct.php?cid=' . $dskapi_cid . '&price=' . $dskapi_price . '&product_id=' . $dskapi_product_id);
            $paramsdskapi = json_decode(curl_exec($dskapi_ch), true);
            curl_close($dskapi_ch);
            
            if (empty($paramsdskapi)){
                return NULL;
            }
            
            $dskapi_zaglavie = $paramsdskapi['dsk_zaglavie'];
            $dskapi_custom_button_status = intval($paramsdskapi['dsk_custom_button_status']);
            $dskapi_options = boolval($paramsdskapi['dsk_options']);
            $dskapi_is_visible = boolval($paramsdskapi['dsk_is_visible']);
            $dskapi_button_normal = DSKAPI_LIVEURL . '/calculators/assets/img/buttons/dsk.png';
            $dskapi_button_normal_custom = DSKAPI_LIVEURL . '/calculators/assets/img/custom_buttons/'.$dskapi_cid.'.png';
            $dskapi_button_hover = DSKAPI_LIVEURL . '/calculators/assets/img/buttons/dsk-hover.png';
            $dskapi_button_hover_custom = DSKAPI_LIVEURL . '/calculators/assets/img/custom_buttons/'.$dskapi_cid.'_hover.png';
            $dskapi_isvnoska = intval($paramsdskapi['dsk_isvnoska']);
            $dskapi_vnoski = intval($paramsdskapi['dsk_vnoski_default']);
            $dskapi_vnoska = floatval($paramsdskapi['dsk_vnoska']);
            $dskapi_button_status = intval($paramsdskapi['dsk_button_status']);
            $dskapi_minstojnost = number_format(floatval($paramsdskapi['dsk_minstojnost']), 2, ".", "");
            $dskapi_maxstojnost = number_format(floatval($paramsdskapi['dsk_maxstojnost']), 2, ".", "");
            $dskapi_vnoski_visible = intval($paramsdskapi['dsk_vnoski_visible']);
            $dskapi_gpr = floatval($paramsdskapi['dsk_gpr']);
            
            $dskapi_vnoski_visible_arr = array();
            if ($dskapi_vnoski_visible & 1){
                $dskapi_vnoski_visible_arr[3] = true;
            }else{
                $dskapi_vnoski_visible_arr[3] = false;
                if ($dskapi_vnoski == 3){
                    $dskapi_vnoski_visible_arr[3] = true;
                }
            }
            if ($dskapi_vnoski_visible & 2){
                $dskapi_vnoski_visible_arr[4] = true;
            }else{
                $dskapi_vnoski_visible_arr[4] = false;
                if ($dskapi_vnoski == 4){
                    $dskapi_vnoski_visible_arr[4] = true;
                }
            }
            if ($dskapi_vnoski_visible & 4){
                $dskapi_vnoski_visible_arr[5] = true;
            }else{
                $dskapi_vnoski_visible_arr[5] = false;
                if ($dskapi_vnoski == 5){
                    $dskapi_vnoski_visible_arr[5] = true;
                }
            }
            if ($dskapi_vnoski_visible & 8){
                $dskapi_vnoski_visible_arr[6] = true;
            }else{
                $dskapi_vnoski_visible_arr[6] = false;
                if ($dskapi_vnoski == 6){
                    $dskapi_vnoski_visible_arr[6] = true;
                }
            }
            if ($dskapi_vnoski_visible & 16){
                $dskapi_vnoski_visible_arr[7] = true;
            }else{
                $dskapi_vnoski_visible_arr[7] = false;
                if ($dskapi_vnoski == 7){
                    $dskapi_vnoski_visible_arr[7] = true;
                }
            }
            if ($dskapi_vnoski_visible & 32){
                $dskapi_vnoski_visible_arr[8] = true;
            }else{
                $dskapi_vnoski_visible_arr[8] = false;
                if ($dskapi_vnoski == 8){
                    $dskapi_vnoski_visible_arr[8] = true;
                }
            }
            if ($dskapi_vnoski_visible & 64){
                $dskapi_vnoski_visible_arr[9] = true;
            }else{
                $dskapi_vnoski_visible_arr[9] = false;
                if ($dskapi_vnoski == 9){
                    $dskapi_vnoski_visible_arr[9] = true;
                }
            }
            if ($dskapi_vnoski_visible & 128){
                $dskapi_vnoski_visible_arr[10] = true;
            }else{
                $dskapi_vnoski_visible_arr[10] = false;
                if ($dskapi_vnoski == 10){
                    $dskapi_vnoski_visible_arr[10] = true;
                }
            }
            if ($dskapi_vnoski_visible & 256){
                $dskapi_vnoski_visible_arr[11] = true;
            }else{
                $dskapi_vnoski_visible_arr[11] = false;
                if ($dskapi_vnoski == 11){
                    $dskapi_vnoski_visible_arr[11] = true;
                }
            }
            if ($dskapi_vnoski_visible & 512){
                $dskapi_vnoski_visible_arr[12] = true;
            }else{
                $dskapi_vnoski_visible_arr[12] = false;
                if ($dskapi_vnoski == 12){
                    $dskapi_vnoski_visible_arr[12] = true;
                }
            }
            if ($dskapi_vnoski_visible & 1024){
                $dskapi_vnoski_visible_arr[13] = true;
            }else{
                $dskapi_vnoski_visible_arr[13] = false;
                if ($dskapi_vnoski == 13){
                    $dskapi_vnoski_visible_arr[13] = true;
                }
            }
            if ($dskapi_vnoski_visible & 2048){
                $dskapi_vnoski_visible_arr[14] = true;
            }else{
                $dskapi_vnoski_visible_arr[14] = false;
                if ($dskapi_vnoski == 14){
                    $dskapi_vnoski_visible_arr[14] = true;
                }
            }
            if ($dskapi_vnoski_visible & 4096){
                $dskapi_vnoski_visible_arr[15] = true;
            }else{
                $dskapi_vnoski_visible_arr[15] = false;
                if ($dskapi_vnoski == 15){
                    $dskapi_vnoski_visible_arr[15] = true;
                }
            }
            if ($dskapi_vnoski_visible & 8192){
                $dskapi_vnoski_visible_arr[16] = true;
            }else{
                $dskapi_vnoski_visible_arr[16] = false;
                if ($dskapi_vnoski == 16){
                    $dskapi_vnoski_visible_arr[16] = true;
                }
            }
            if ($dskapi_vnoski_visible & 16384){
                $dskapi_vnoski_visible_arr[17] = true;
            }else{
                $dskapi_vnoski_visible_arr[17] = false;
                if ($dskapi_vnoski == 18){
                    $dskapi_vnoski_visible_arr[18] = true;
                }
            }
            if ($dskapi_vnoski_visible & 32768){
                $dskapi_vnoski_visible_arr[18] = true;
            }else{
                $dskapi_vnoski_visible_arr[18] = false;
                if ($dskapi_vnoski == 19){
                    $dskapi_vnoski_visible_arr[19] = true;
                }
            }
            if ($dskapi_vnoski_visible & 65536){
                $dskapi_vnoski_visible_arr[19] = true;
            }else{
                $dskapi_vnoski_visible_arr[19] = false;
                if ($dskapi_vnoski == 19){
                    $dskapi_vnoski_visible_arr[19] = true;
                }
            }
            if ($dskapi_vnoski_visible & 131072){
                $dskapi_vnoski_visible_arr[20] = true;
            }else{
                $dskapi_vnoski_visible_arr[20] = false;
                if ($dskapi_vnoski == 20){
                    $dskapi_vnoski_visible_arr[20] = true;
                }
            }
            if ($dskapi_vnoski_visible & 262144){
                $dskapi_vnoski_visible_arr[21] = true;
            }else{
                $dskapi_vnoski_visible_arr[21] = false;
                if ($dskapi_vnoski == 21){
                    $dskapi_vnoski_visible_arr[21] = true;
                }
            }
            if ($dskapi_vnoski_visible & 524288){
                $dskapi_vnoski_visible_arr[22] = true;
            }else{
                $dskapi_vnoski_visible_arr[22] = false;
                if ($dskapi_vnoski == 22){
                    $dskapi_vnoski_visible_arr[22] = true;
                }
            }
            if ($dskapi_vnoski_visible & 1048576){
                $dskapi_vnoski_visible_arr[23] = true;
            }else{
                $dskapi_vnoski_visible_arr[23] = false;
                if ($dskapi_vnoski == 23){
                    $dskapi_vnoski_visible_arr[23] = true;
                }
            }
            if ($dskapi_vnoski_visible & 2097152){
                $dskapi_vnoski_visible_arr[24] = true;
            }else{
                $dskapi_vnoski_visible_arr[24] = false;
                if ($dskapi_vnoski == 24){
                    $dskapi_vnoski_visible_arr[24] = true;
                }
            }
            if ($dskapi_vnoski_visible & 4194304){
                $dskapi_vnoski_visible_arr[25] = true;
            }else{
                $dskapi_vnoski_visible_arr[25] = false;
                if ($dskapi_vnoski == 25){
                    $dskapi_vnoski_visible_arr[25] = true;
                }
            }
            if ($dskapi_vnoski_visible & 8388608){
                $dskapi_vnoski_visible_arr[26] = true;
            }else{
                $dskapi_vnoski_visible_arr[26] = false;
                if ($dskapi_vnoski == 26){
                    $dskapi_vnoski_visible_arr[26] = true;
                }
            }
            if ($dskapi_vnoski_visible & 16777216){
                $dskapi_vnoski_visible_arr[27] = true;
            }else{
                $dskapi_vnoski_visible_arr[27] = false;
                if ($dskapi_vnoski == 27){
                    $dskapi_vnoski_visible_arr[27] = true;
                }
            }
            if ($dskapi_vnoski_visible & 33554432){
                $dskapi_vnoski_visible_arr[28] = true;
            }else{
                $dskapi_vnoski_visible_arr[28] = false;
                if ($dskapi_vnoski == 28){
                    $dskapi_vnoski_visible_arr[28] = true;
                }
            }
            if ($dskapi_vnoski_visible & 67108864){
                $dskapi_vnoski_visible_arr[29] = true;
            }else{
                $dskapi_vnoski_visible_arr[29] = false;
                if ($dskapi_vnoski == 29){
                    $dskapi_vnoski_visible_arr[29] = true;
                }
            }
            if ($dskapi_vnoski_visible & 134217728){
                $dskapi_vnoski_visible_arr[30] = true;
            }else{
                $dskapi_vnoski_visible_arr[30] = false;
                if ($dskapi_vnoski == 30){
                    $dskapi_vnoski_visible_arr[30] = true;
                }
            }
            if ($dskapi_vnoski_visible & 268435456){
                $dskapi_vnoski_visible_arr[31] = true;
            }else{
                $dskapi_vnoski_visible_arr[31] = false;
                if ($dskapi_vnoski == 31){
                    $dskapi_vnoski_visible_arr[31] = true;
                }
            }
            if ($dskapi_vnoski_visible & 536870912){
                $dskapi_vnoski_visible_arr[32] = true;
            }else{
                $dskapi_vnoski_visible_arr[32] = false;
                if ($dskapi_vnoski == 32){
                    $dskapi_vnoski_visible_arr[32] = true;
                }
            }
            if ($dskapi_vnoski_visible & 1073741824){
                $dskapi_vnoski_visible_arr[33] = true;
            }else{
                $dskapi_vnoski_visible_arr[33] = false;
                if ($dskapi_vnoski == 33){
                    $dskapi_vnoski_visible_arr[33] = true;
                }
            }
            if ($dskapi_vnoski_visible & 2147483648){
                $dskapi_vnoski_visible_arr[34] = true;
            }else{
                $dskapi_vnoski_visible_arr[34] = false;
                if ($dskapi_vnoski == 34){
                    $dskapi_vnoski_visible_arr[34] = true;
                }
            }
            if ($dskapi_vnoski_visible & 4294967296){
                $dskapi_vnoski_visible_arr[35] = true;
            }else{
                $dskapi_vnoski_visible_arr[35] = false;
                if ($dskapi_vnoski == 35){
                    $dskapi_vnoski_visible_arr[35] = true;
                }
            }
            if ($dskapi_vnoski_visible & 8589934592){
                $dskapi_vnoski_visible_arr[36] = true;
            }else{
                $dskapi_vnoski_visible_arr[36] = false;
                if ($dskapi_vnoski == 36){
                    $dskapi_vnoski_visible_arr[36] = true;
                }
            }
            if ($dskapi_vnoski_visible & 17179869184){
                $dskapi_vnoski_visible_arr[37] = true;
            }else{
                $dskapi_vnoski_visible_arr[37] = false;
                if ($dskapi_vnoski == 37){
                    $dskapi_vnoski_visible_arr[37] = true;
                }
            }
            if ($dskapi_vnoski_visible & 34359738368){
                $dskapi_vnoski_visible_arr[38] = true;
            }else{
                $dskapi_vnoski_visible_arr[38] = false;
                if ($dskapi_vnoski == 38){
                    $dskapi_vnoski_visible_arr[38] = true;
                }
            }
            if ($dskapi_vnoski_visible & 68719476736){
                $dskapi_vnoski_visible_arr[39] = true;
            }else{
                $dskapi_vnoski_visible_arr[39] = false;
                if ($dskapi_vnoski == 39){
                    $dskapi_vnoski_visible_arr[39] = true;
                }
            }
            if ($dskapi_vnoski_visible & 137438953472){
                $dskapi_vnoski_visible_arr[40] = true;
            }else{
                $dskapi_vnoski_visible_arr[40] = false;
                if ($dskapi_vnoski == 40){
                    $dskapi_vnoski_visible_arr[40] = true;
                }
            }
            if ($dskapi_vnoski_visible & 274877906944){
                $dskapi_vnoski_visible_arr[41] = true;
            }else{
                $dskapi_vnoski_visible_arr[41] = false;
                if ($dskapi_vnoski == 41){
                    $dskapi_vnoski_visible_arr[41] = true;
                }
            }
            if ($dskapi_vnoski_visible & 549755813888){
                $dskapi_vnoski_visible_arr[42] = true;
            }else{
                $dskapi_vnoski_visible_arr[42] = false;
                if ($dskapi_vnoski == 42){
                    $dskapi_vnoski_visible_arr[42] = true;
                }
            }
            if ($dskapi_vnoski_visible & 1099511627776){
                $dskapi_vnoski_visible_arr[43] = true;
            }else{
                $dskapi_vnoski_visible_arr[43] = false;
                if ($dskapi_vnoski == 43){
                    $dskapi_vnoski_visible_arr[43] = true;
                }
            }
            if ($dskapi_vnoski_visible & 2199023255552){
                $dskapi_vnoski_visible_arr[44] = true;
            }else{
                $dskapi_vnoski_visible_arr[44] = false;
                if ($dskapi_vnoski == 44){
                    $dskapi_vnoski_visible_arr[44] = true;
                }
            }
            if ($dskapi_vnoski_visible & 4398046511104){
                $dskapi_vnoski_visible_arr[45] = true;
            }else{
                $dskapi_vnoski_visible_arr[45] = false;
                if ($dskapi_vnoski == 45){
                    $dskapi_vnoski_visible_arr[45] = true;
                }
            }
            if ($dskapi_vnoski_visible & 8796093022208){
                $dskapi_vnoski_visible_arr[46] = true;
            }else{
                $dskapi_vnoski_visible_arr[46] = false;
                if ($dskapi_vnoski == 46){
                    $dskapi_vnoski_visible_arr[46] = true;
                }
            }
            if ($dskapi_vnoski_visible & 17592186044416){
                $dskapi_vnoski_visible_arr[47] = true;
            }else{
                $dskapi_vnoski_visible_arr[47] = false;
                if ($dskapi_vnoski == 47){
                    $dskapi_vnoski_visible_arr[47] = true;
                }
            }
            if ($dskapi_vnoski_visible & 35184372088832){
                $dskapi_vnoski_visible_arr[48] = true;
            }else{
                $dskapi_vnoski_visible_arr[48] = false;
                if ($dskapi_vnoski == 48){
                    $dskapi_vnoski_visible_arr[48] = true;
                }
            }
            
            $useragent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : '';
            $dskapi_is_mobile = preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4));
            if($dskapi_is_mobile){
                $dskapi_PopUp_Detailed_v1 = "dskapim_PopUp_Detailed_v1";
                $dskapi_Mask = "dskapim_Mask";
                $dskapi_picture = DSKAPI_LIVEURL . '/calculators/assets/img/dskm' . $paramsdskapi['dsk_reklama'] . '.png';
                $dskapi_product_name = "dskapim_product_name";
                $dskapi_body_panel_txt3 = "dskapim_body_panel_txt3";
                $dskapi_body_panel_txt4 = "dskapim_body_panel_txt4";
                $dskapi_body_panel_txt3_left = "dskapim_body_panel_txt3_left";
                $dskapi_body_panel_txt3_right = "dskapim_body_panel_txt3_right";
                $dskapi_sumi_panel = "dskapim_sumi_panel";
                $dskapi_kredit_panel = "dskapim_kredit_panel";
                $dskapi_body_panel_footer = "dskapim_body_panel_footer";
                $dskapi_body_panel_left = "dskapim_body_panel_left";
            }else{
                $dskapi_PopUp_Detailed_v1 = "dskapi_PopUp_Detailed_v1";
                $dskapi_Mask = "dskapi_Mask";
                $dskapi_picture = DSKAPI_LIVEURL . '/calculators/assets/img/dsk' . $paramsdskapi['dsk_reklama'] . '.png';
                $dskapi_product_name = "dskapi_product_name";
                $dskapi_body_panel_txt3 = "dskapi_body_panel_txt3";
                $dskapi_body_panel_txt4 = "dskapi_body_panel_txt4";
                $dskapi_body_panel_txt3_left = "dskapi_body_panel_txt3_left";
                $dskapi_body_panel_txt3_right = "dskapi_body_panel_txt3_right";
                $dskapi_sumi_panel = "dskapi_sumi_panel";
                $dskapi_kredit_panel = "dskapi_kredit_panel";
                $dskapi_body_panel_footer = "dskapi_body_panel_footer";
                $dskapi_body_panel_left = "dskapi_body_panel_left";
            }
            
            if ((!$dskapi_is_empty) && ($dskapi_options) && $dskapi_is_visible && ($paramsdskapi['dsk_status'] == 1) && ($dskapi_button_status != 0)) {
                ?>
                <div id="dskapi-product-button-container">
                    <table class="dskapi_table">
                        <tr>
                            <td class="dskapi_button_table">
                                <div class="dskapi_button_div_txt">
                                <?php echo $dskapi_zaglavie; ?>
                                </div>
                            </td>
                        </tr>
                    </table>
                    <table class="dskapi_table_img">
                        <tr>
                            <td class="dskapi_button_table">
                                <?php if ($dskapi_custom_button_status == 1){ ?>
                                    <img id="btn_dskapi" class="dskapi_btn_click dskapi_logo" src="<?php echo $dskapi_button_normal_custom; ?>" alt="Кредитен калкулатор DSK Credit" onmouseover="this.src='<?php echo $dskapi_button_hover_custom; ?>'" onmouseout="this.src='<?php echo $dskapi_button_normal_custom; ?>'" />
                                <?php }else{ ?>
                                    <img id="btn_dskapi" class="dskapi_btn_click dskapi_logo" src="<?php echo $dskapi_button_normal; ?>" alt="Кредитен калкулатор DSK Credit" onmouseover="this.src='<?php echo $dskapi_button_hover; ?>'" onmouseout="this.src='<?php echo $dskapi_button_normal; ?>'" />
                                <?php } ?>
                            </td>
                        </tr>
                        <?php if ($dskapi_isvnoska == 1){ ?>
                        <tr>
                            <td class="dskapi_button_table">
                                <p><?php echo $dskapi_vnoski; ?> x <?php echo number_format($dskapi_vnoska, 2, '.', ''); ?> <?php echo $dskapi_sign; ?></p>
                            </td>
                        </tr>
                        <?php } ?>
                    </table>
                </div>
                <input type="hidden" id="dskapi_price" value="<?php echo wc_get_price_including_tax($product); ?>" />
                <input type="hidden" id="dskapi_cid" value="<?php echo $dskapi_cid; ?>" />
                <input type="hidden" id="dskapi_product_id" value="<?php echo $dskapi_product_id; ?>" />
                <input type="hidden" id="DSKAPI_LIVEURL" value="<?php echo DSKAPI_LIVEURL; ?>" />
                <input type="hidden" id="dskapi_button_status" value="<?php echo $dskapi_button_status; ?>" />
                <input type="hidden" id="dskapi_maxstojnost" value="<?php echo $dskapi_maxstojnost; ?>" />
                <input type="hidden" id="dskapi_eur" value="<?php echo $dskapi_eur; ?>" />
                <input type="hidden" id="dskapi_currency_code" value="<?php echo $dskapi_currency_code; ?>" />
                <div id="dskapi-product-popup-container" class="modalpayment_dskapi">
                    <div class="modalpayment-content_dskapi">
                        <div id="dskapi_body">
                            <div class="<?php echo $dskapi_PopUp_Detailed_v1; ?>">
                                <div class="<?php echo $dskapi_Mask; ?>">
                                    <img src="<?php echo $dskapi_picture; ?>" class="dskapi_header">
                                    <p class="<?php echo $dskapi_product_name; ?>">Купи на изплащане със стоков кредит от Банка ДСК</p>
                                    <div class="<?php echo $dskapi_body_panel_txt3; ?>">
                                        <div class="<?php echo $dskapi_body_panel_txt3_left; ?>">
                                            <p>
                                            •    Улеснена процедура за електронно подписване<br />
                                            •    Атрактивни условия по кредита<br />
                                            •    Параметри изцяло по Ваш избор<br />
                                            •    Одобрение до няколко минути изцяло онлайн
                                            </p>
                                        </div>
                                        <div class="<?php echo $dskapi_body_panel_txt3_right; ?>">
                                            <select id="dskapi_pogasitelni_vnoski_input" class="dskapi_txt_right" onchange="dskapi_pogasitelni_vnoski_input_change();" onfocus="dskapi_pogasitelni_vnoski_input_focus(this.value);">
                                                <?php for ($i = 3; $i <= 48; $i++){ ?>
                                                    <?php if ($dskapi_vnoski_visible_arr[$i]){ ?>}
                                                    <option value="<?php echo $i; ?>" <?php if ($dskapi_vnoski == $i){echo "selected";} ?>><?php echo $i; ?> месеца</option>
                                                    <?php } ?>
                                                <?php } ?>
                                            </select>
                                            <div class="<?php echo $dskapi_sumi_panel; ?>">
                                                <div class="<?php echo $dskapi_kredit_panel; ?>">
                                                    <div class="dskapi_sumi_txt">Размер на кредита /<?php echo $dskapi_sign; ?>/</div>
                                                    <div>
                                                        <input class="dskapi_mesecna_price" type="text" id="dskapi_price_txt" readonly="readonly" value="<?php echo number_format($dskapi_price, 2, ".", ""); ?>"/>
                                                    </div>
                                                </div>
                                                <div class="<?php echo $dskapi_kredit_panel; ?>">
                                                    <div class="dskapi_sumi_txt">Месечна вноска /<?php echo $dskapi_sign; ?>/</div>
                                                    <div>
                                                        <input class="dskapi_mesecna_price" type="text" id="dskapi_vnoska" readonly="readonly" value="<?php echo number_format($dskapi_vnoska, 2, ".", ""); ?>"/>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="<?php echo $dskapi_sumi_panel; ?>">
                                                <div class="<?php echo $dskapi_kredit_panel; ?>">
                                                    <div class="dskapi_sumi_txt">Обща дължима сума /<?php echo $dskapi_sign; ?>/</div>
                                                    <div>
                                                        <input class="dskapi_mesecna_price" type="text" id="dskapi_obshtozaplashtane" readonly="readonly" value="<?php echo number_format($dskapi_vnoska * $dskapi_vnoski, 2, ".", ""); ?>" />
                                                    </div>
                                                </div>
                                                <div class="<?php echo $dskapi_kredit_panel; ?>">
                                                    <div class="dskapi_sumi_txt">ГПР /%/</div>
                                                    <div>
                                                        <input class="dskapi_mesecna_price" type="text" id="dskapi_gpr" readonly="readonly" value="<?php echo number_format($dskapi_gpr, 2, ".", ""); ?>" />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="<?php echo $dskapi_body_panel_txt4; ?>">
                                        Изчисленията са направени при допускането за първа падежна дата след 30 дни и са с насочваща цел. Избери най-подходящата месечна вноска.
                                    </div>
                                    <div class="<?php echo $dskapi_body_panel_footer; ?>">
                                        <div class="dskapi_btn" id="dskapi_buy_credit" >Добави в количката</div>
                                        <div class="dskapi_btn_cancel" id="dskapi_back_credit" >Откажи</div>
                                        <div class="<?php echo $dskapi_body_panel_left; ?>">
                                            <div class="dskapi_txt_footer">Ver. <?php echo DSKAPI_VERSION; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
    }
    
    function dskapi_updateorder() {
        $json = array();
        $json['success'] = 'unsuccess';
        
        $dskapi_cid = (string)get_option("dskapi_cid");
        
        if (isset($_REQUEST['order_id'])) {
            $dskapi_order_id = $_REQUEST['order_id'];
        } else {
            $dskapi_order_id = '';
        }
        
        if (isset($_REQUEST['status'])) {
            $dskapi_status = $_REQUEST['status'];
        } else {
            $dskapi_status = 0;
        }
        
        if (isset($_REQUEST['calculator_id'])) {
            $dskapi_calculator_id = $_REQUEST['calculator_id'];
        } else {
            $dskapi_calculator_id = '';
        }
        
        if (($dskapi_calculator_id != '') && ($dskapi_cid == $dskapi_calculator_id)){
            if (file_exists(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json')) {
                $orderdata = file_get_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json');
                $dskapi_orderdata_all = json_decode($orderdata, true);
                foreach ($dskapi_orderdata_all as $key => $value){
                    if ($dskapi_orderdata_all[$key]['order_id'] == $dskapi_order_id){
                        $dskapi_orderdata_all[$key]['order_status'] = $dskapi_status;
                    }
                }
                $jsondata = json_encode($dskapi_orderdata_all);
                file_put_contents(DSKAPI_PLUGIN_DIR . '/keys/dskapiorders.json', $jsondata);
                $json['success'] = 'success';
            }
        }
        
        $json['dskapi_order_id'] = $dskapi_order_id;
        $json['dskapi_status'] = $dskapi_status;
        $json['dskapi_calculator_id'] = $dskapi_calculator_id;
        
        echo (json_encode($json));
        die();
    }
