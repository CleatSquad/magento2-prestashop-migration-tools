SELECT 
    prdl.id_product,
    l.language_code,
    prd.reference,
    prdl.name,
    prdl.description,
    prdl.description_short,
    prdl.link_rewrite,
    prd.active,
    prd.weight,
    CASE 
        WHEN prdshop.visibility = 'both' THEN 4
        WHEN prdshop.visibility = 'catalog' THEN 2
        WHEN prdshop.visibility = 'search' THEN 3
        ELSE 1
    END as visibility,
    sav.quantity,
    prd.minimal_quantity,
    IF (prd.out_of_stock = 1,'1','0') as backorders,
    IF (sav.quantity = 0,'0','1') as is_in_stock,
    CASE 
        WHEN prdshop.cache_default_attribute = '0' THEN 'simple'
        WHEN prd.is_virtual = '1' THEN 'virtual'
	ELSE 'configurable'
    END as type_id,
    prd.id_tax_rules_group as tva,
    prdshop.price,
    prdshop.wholesale_price,
    prdl.meta_title,
    prdl.meta_keywords,
    prdl.meta_description
FROM
    ps_product_lang AS prdl
LEFT JOIN ps_product AS prd ON prd.id_product = prdl.id_product 
LEFT JOIN ps_product_shop AS prdshop ON prd.id_product = prdshop.id_product 
LEFT JOIN ps_stock_available sav ON (sav.id_product = prd.id_product AND sav.id_product_attribute = 0 AND sav.id_shop = 1  AND sav.id_shop_group = 0 )
LEFT JOIN ps_lang AS l ON l.id_lang = prdl.id_lang
LEFT JOIN ps_specific_price pr ON(prdl.id_product = pr.id_product)