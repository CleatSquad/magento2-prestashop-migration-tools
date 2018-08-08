SELECT 
    cat.id_parent,
    l.language_code,
    cat.id_category,
    catl.name,
    cat.level_depth,
    cat.position,
    catl.description,
    catl.link_rewrite,
    cat.active,
    catl.meta_title,
    catl.meta_keywords,
    catl.meta_description,
    cat.is_root_category 
FROM 
    ps_category AS cat 
LEFT JOIN ps_category_lang AS catl ON cat.id_category = catl.id_category 
LEFT JOIN ps_lang AS l ON l.id_lang = catl.id_lang