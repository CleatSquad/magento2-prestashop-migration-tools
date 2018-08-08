SELECT 
    prd.id_product,
    CONCAT('http://',IFNULL(conf.value, 'undefined_domain'),'/img/p/', IF(CHAR_LENGTH(pi.id_image) >= 5,CONCAT( SUBSTRING(pi.id_image, -5, 1),'/'),''),IF(CHAR_LENGTH(pi.id_image) >= 4, CONCAT(SUBSTRING(pi.id_image, -4, 1), '/'), ''),IF(CHAR_LENGTH(pi.id_image) >= 3, CONCAT(SUBSTRING(pi.id_image, -3, 1), '/'), ''),if(CHAR_LENGTH(pi.id_image) >= 2, CONCAT(SUBSTRING(pi.id_image, -2, 1), '/'), ''),IF(CHAR_LENGTH(pi.id_image) >= 1, CONCAT(SUBSTRING(pi.id_image, -1, 1), '/'), ''),pi.id_image,'.jpg') AS image,
    pi.cover
FROM
    ps_product AS prd
INNER JOIN ps_image pi ON(prd.id_product = pi.id_product)
LEFT JOIN ps_configuration conf ON conf.name = 'PS_SHOP_DOMAIN'
WHERE !is_null(image)