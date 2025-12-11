<?php
/** add admin menu */
function dskapi_admin_actions() {
    add_options_page('DSK Credit API - Настройки на модула', 'DSK Credit API настойки', 'manage_options', "dskapi-options", "dskapi_admin_options");
}