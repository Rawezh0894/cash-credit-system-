-- Remove settings table
DROP TABLE IF EXISTS `settings`;

-- Remove settings-related permissions
DELETE FROM `permissions` WHERE `name` IN ('view_settings', 'edit_settings');

-- Remove settings permissions from role_permissions
DELETE FROM `role_permissions` WHERE `permission_id` IN (
    SELECT `id` FROM `permissions` WHERE `name` IN ('view_settings', 'edit_settings')
); 