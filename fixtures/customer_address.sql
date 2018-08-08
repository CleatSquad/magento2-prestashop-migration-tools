SELECT 
    addr.`id_address`,
    addr.`id_customer`,
    addr.`firstname`,
    addr.`lastname`,
    addr.`company`,
    addr.`phone`,
    addr.`address1`,
    addr.`address2`,
    addr.`postcode`,
    country.`iso_code`,
    state.`name`,
    addr.`city`
FROM `ps_address` AS addr
LEFT JOIN ps_country AS country ON addr.id_country = country.id_country
LEFT JOIN ps_state AS state ON addr.id_state = state.id_state
WHERE addr.`deleted` = 0 AND addr.`id_customer` != 0