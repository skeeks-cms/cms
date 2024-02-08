
UPDATE
    `cms_content_element_property` AS need_update
INNER JOIN(
    SELECT ccep.id AS property_for_update_id
    FROM
        `cms_content_element_property` AS ccep
    LEFT JOIN cms_content_property AS ccp
    ON
        ccp.id = ccep.property_id
    WHERE
        ccp.property_type != "B"
) AS property_for_update
ON
    property_for_update.property_for_update_id = need_update.id
SET
    need_update.`value_bool` = NULL