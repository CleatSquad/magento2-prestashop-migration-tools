SELECT 
    prd.id_product,
    catprd.id_category
FROM
    ps_product AS prd
LEFT JOIN ps_category_product AS catprd ON catprd.id_product = prd.id_product