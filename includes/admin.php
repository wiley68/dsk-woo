<?php

/** add admin menu */
function dskapi_admin_actions()
{
    add_options_page('Банка ДСК покупки на Кредит  - Настройки на модула', 'Банка ДСК покупки на Кредит', 'manage_options', "dskapi-options", "dskapi_admin_options");
}
