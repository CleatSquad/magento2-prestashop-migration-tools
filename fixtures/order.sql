SELECT 
    `id_order`,
    l.language_code,
    `id_customer`,
    `id_cart`,
    `id_currency`,
    `id_address_delivery`,
    `id_address_invoice`
FROM 
    `ps_orders` AS o 
LEFT JOIN ps_lang AS l ON l.id_lang = o.id_lang