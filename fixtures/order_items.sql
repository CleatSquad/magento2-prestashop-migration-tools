SELECT 
    od.`id_order`,
    o.`id_cart`,
    `product_id`,
    `product_quantity`,
    `product_price`
FROM `ps_order_detail` AS od
LEFT JOIN ps_orders AS o ON o.id_order = od.id_order