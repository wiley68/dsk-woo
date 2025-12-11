const settings_dskapi = window.wc.wcSettings.getSetting( 'dskapipayment_data', {} );
const label_dskapi = window.wp.htmlEntities.decodeEntities( settings_dskapi.title ) || 'DSK Credit';
const Content_dskapi = () => {
    return window.wp.htmlEntities.decodeEntities( settings_dskapi.description || 'Дава възможност на Вашите клиенти да закупуват стока на изплащане с DSK Credit' );
};
const Block_Gateway_Dskapi = {
    name: 'dskapipayment',
    label: label_dskapi,
    content: Object( window.wp.element.createElement )( Content_dskapi, null ),
    edit: Object( window.wp.element.createElement )( Content_dskapi, null ),
    canMakePayment: () => true,
    ariaLabel: label_dskapi,
    supports: {
        features: settings_dskapi.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway_Dskapi );