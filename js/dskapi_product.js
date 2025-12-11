let old_vnoski;

function dskapiConvertToDotDecimal(price) {
    price = price.trim();
    if (price.includes('.') && price.includes(',')) {
        if (price.lastIndexOf(',') < price.lastIndexOf('.')) {
            price = price.replace(/,/g, '');
        } else {
            price = price.replace(/\./g, '').replace(/,/g, '.');
        }
    } else if (price.includes(',')) {
        if (price.split(',').length - 1 === 1) {
            price = price.replace(/,/g, '.');
        } else {
            price = price.replace(/,/g, '');
        }
    }
    return price;
}

function createCORSRequest(method, url) {
    var xhr = new XMLHttpRequest();
    if ("withCredentials" in xhr) {
        xhr.open(method, url, true);
    } else if (typeof XDomainRequest != "undefined") {
        xhr = new XDomainRequest();
        xhr.open(method, url);
    } else {
        xhr = null;
    }
    return xhr;
}

function dskapi_pogasitelni_vnoski_input_focus(_old_vnoski) {
    old_vnoski = _old_vnoski;
}

function dskapi_pogasitelni_vnoski_input_change() {
    const dskapi_vnoski = parseFloat(document.getElementById("dskapi_pogasitelni_vnoski_input").value);
    const dskapi_price = parseFloat(document.getElementById("dskapi_price_txt").value);
    const dskapi_cid = document.getElementById("dskapi_cid").value;
    const DSKAPI_LIVEURL = document.getElementById("DSKAPI_LIVEURL").value;
    const dskapi_product_id = document.getElementById("dskapi_product_id").value;
    var xmlhttpro = createCORSRequest("GET", DSKAPI_LIVEURL + '/function/getproductcustom.php?cid='+dskapi_cid+'&price='+dskapi_price+'&product_id='+dskapi_product_id+'&dskapi_vnoski='+dskapi_vnoski);
    xmlhttpro.onreadystatechange = function() {
        if (this.readyState == 4) {
            var options = JSON.parse(this.response).dsk_options;
            var dsk_vnoska = parseFloat(JSON.parse(this.response).dsk_vnoska);
            var dsk_gpr = parseFloat(JSON.parse(this.response).dsk_gpr);
            var dsk_is_visible = JSON.parse(this.response).dsk_is_visible;
            if (dsk_is_visible){
                if (options){
                    const dskapi_vnoska_input = document.getElementById("dskapi_vnoska");
                    const dskapi_gpr = document.getElementById("dskapi_gpr");
                    const dskapi_obshtozaplashtane_input = document.getElementById("dskapi_obshtozaplashtane");
                    dskapi_vnoska_input.value = dsk_vnoska.toFixed(2);
                    dskapi_obshtozaplashtane_input.value = (dsk_vnoska * dskapi_vnoski).toFixed(2);
                    dskapi_gpr.value = dsk_gpr.toFixed(2);
                    old_vnoski = dskapi_vnoski;
                }else{
                    alert ("Избраният брой погасителни вноски е под минималния.");
                    var dskapi_vnoski_input = document.getElementById("dskapi_pogasitelni_vnoski_input");
                    dskapi_vnoski_input.value = old_vnoski;
                }
            }else{
                alert ("Избраният брой погасителни вноски е над максималния.");
                var dskapi_vnoski_input = document.getElementById("dskapi_pogasitelni_vnoski_input");
                dskapi_vnoski_input.value = old_vnoski;
            }
        }
    }
    xmlhttpro.send();
}

document.addEventListener("DOMContentLoaded", function() {
    const btn_dskapi = document.getElementById("btn_dskapi");
    if (btn_dskapi !== null) {
        const dskapi_button_status = parseInt(document.getElementById("dskapi_button_status").value);
        const dskapiProductPopupContainer = document.getElementById("dskapi-product-popup-container");
        const dskapi_back_credit = document.getElementById("dskapi_back_credit");
        const dskapi_buy_credit = document.getElementById("dskapi_buy_credit");
        const dskapi_buy_buttons_submit = document.querySelectorAll('button[type="submit"].single_add_to_cart_button');
        
        const dskapi_price = document.getElementById('dskapi_price');
        const dskapi_maxstojnost = document.getElementById('dskapi_maxstojnost');
        let dskapi_price1 = dskapi_price.value;
        let dskapi_quantity = 1;
        let dskapi_priceall = parseFloat(dskapi_price1) * dskapi_quantity;
        
        btn_dskapi.addEventListener('click', event => {
            const dskapi_eur = parseInt(document.getElementById("dskapi_eur").value);
            const dskapi_currency_code = document.getElementById("dskapi_currency_code").value;
            if (dskapi_button_status == 1){
                if (dskapi_buy_buttons_submit.length){
                    dskapi_buy_buttons_submit.item(0).click();
                }
            }else{
                //get price with options
                var variationDiv = document.getElementsByClassName("woocommerce-variation-price");
                if (typeof variationDiv[0] !== 'undefined'){
                    var variationSpan1 = variationDiv[0].getElementsByTagName("span");
                    if (typeof variationSpan1[0] !== 'undefined'){
                        var variationSpan2 = variationSpan1[0].getElementsByTagName("span");
                        if (typeof variationSpan2[0] !== 'undefined'){
                            var tps = variationSpan2[0].innerHTML.split("&");
                            dskapi_price1 = tps[0];
                        }
                        var variationIns = variationSpan1[0].getElementsByTagName("ins");
                        if (typeof variationIns[0] !== 'undefined'){
                            var variationSpan3 = variationIns[0].getElementsByTagName("span");
                            if (typeof variationSpan3[0] !== 'undefined'){
                                var tps = variationSpan3[0].innerHTML.split("&");
                                dskapi_price1 = tps[0];
                            }
                        }
                    }
                }
                dskapi_price1 = dskapi_price1.replace(/[^\d.,]/g, '');
                dskapi_price1 = dskapiConvertToDotDecimal(dskapi_price1);
                
                if (document.getElementsByName("quantity") !== null){
                    dskapi_quantity = parseFloat(document.getElementsByName("quantity")[0].value);
                }
                dskapi_priceall = parseFloat(dskapi_price1) * dskapi_quantity;
                
                switch (dskapi_eur) {
                    case 0:
                        break;
                    case 1:
                        if (dskapi_currency_code == "EUR") {
                            dskapi_priceall = dskapi_priceall * 1.95583;
                        }
                        break;
                    case 2:
                        if (dskapi_currency_code == "BGN") {
                            dskapi_priceall = dskapi_priceall / 1.95583;
                        }
                        break;
                }
                
                const dskapi_price_txt = document.getElementById('dskapi_price_txt');
                dskapi_price_txt.value = dskapi_priceall.toFixed(2);
                if (dskapi_priceall <= parseFloat(dskapi_maxstojnost.value)){
                    dskapiProductPopupContainer.style.display = "block";
                    dskapi_pogasitelni_vnoski_input_change();
                }else{
                    alert("Максимално позволената цена за кредит " + parseFloat(dskapi_maxstojnost.value).toFixed(2) + " е надвишена!");
                }
            }
        });
        dskapi_back_credit.addEventListener('click', event => {
            dskapiProductPopupContainer.style.display = "none";
        });
        dskapi_buy_credit.addEventListener('click', event => {
            dskapiProductPopupContainer.style.display = "none";
            if (dskapi_buy_buttons_submit.length){
                dskapi_buy_buttons_submit.item(0).click();
            }
        });
    }
    
});