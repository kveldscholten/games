<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Gamelist\Config;

class Config extends \Ilch\Config\Install
{
    public $config = [
        'key' => 'gamelist',
        'version' => '1.4.0',
        'icon_small' => 'fa-gamepad',
        'author' => 'Veldscholten, Kevin',
        'link' => 'http://ilch.de',
        'languages' => [
            'de_DE' => [
                'name' => 'Spieleliste',
                'description' => 'Hier kannst du die Spieleliste verwalten.',
            ],
            'en_EN' => [
                'name' => 'Game list',
                'description' => 'Here you can manage the game list.',
            ],
        ],
        'ilchCore' => '2.1.18',
        'phpVersion' => '5.6'
    ];

    public function install()
    {
        $this->db()->queryMulti($this->getInstallSql());
    }

    public function uninstall()
    {
        $this->db()->queryMulti('DROP TABLE `[prefix]_gamelist`;
                                 DROP TABLE `[prefix]_gamelist_cats`;
                                 DROP TABLE `[prefix]_gamelist_entrants`;');
        $this->db()->queryMulti("DELETE FROM `[prefix]_user_menu_settings_links` WHERE `key` = 'gamelist/index/settings';");

        $profileFieldId = (int) $this->db()->select('id')
            ->from('profile_fields')
            ->where(['key' => 'gamelist_games'])
            ->execute()
            ->fetchCell();

        $this->db()->queryMulti("DELETE FROM `[prefix]_profile_fields` WHERE `id` = ".$profileFieldId.";
            DELETE FROM `[prefix]_profile_content` WHERE `field_id` = ".$profileFieldId.";
            DELETE FROM `[prefix]_profile_trans` WHERE `field_id` = ".$profileFieldId.";");
    }

    public function getInstallSql()
    {
        $installSql =
            'CREATE TABLE IF NOT EXISTS `[prefix]_gamelist` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `catid` INT(11) NOT NULL,
                `title` VARCHAR(100) NOT NULL,
                `videourl` VARCHAR(100) NOT NULL,
                `image` VARCHAR(255) NULL DEFAULT NULL,
                `show` TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

            CREATE TABLE IF NOT EXISTS `[prefix]_gamelist_entrants` (
                `game_id` INT(11) NOT NULL,
                `user_id` INT(11) UNSIGNED NOT NULL,
                CONSTRAINT `[prefix]_gamelist_entrants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `[prefix]_users`(`id`) ON DELETE CASCADE 
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `[prefix]_gamelist_cats` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `title` VARCHAR(100) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;

            INSERT INTO `[prefix]_user_menu_settings_links` (`key`, `locale`, `description`, `name`) VALUES
                ("gamelist/index/settings", "de_DE", "Hier kannst du deine Spielliste bearbeiten.", "Spieleauswahl"),
                ("gamelist/index/settings", "en_EN", "Here you can manage your game list.", "Games selection");

            INSERT INTO `[prefix]_profile_fields` (`key`, `type`, `show`, `hidden`, `position`) VALUES ("gamelist_games", 0, 1, 1, 0);

            INSERT INTO `[prefix]_profile_trans` (`field_id`, `locale`, `name`) VALUES
              (LAST_INSERT_ID(), "de_DE", "Spiele"),
              (LAST_INSERT_ID(), "en_EN", "Games");';

        return $installSql;
    }

    public function getUpdate($installedVersion)
    {
        switch ($installedVersion) {
            case "1.0.0":
            case "1.1.0":
                // Create table gamelist_cats if it doesn't already exist.
                if (!$this->db()->ifTableExists('[prefix]_gamelist_cats')) {
                    $this->db()->query('CREATE TABLE IF NOT EXISTS `[prefix]_gamelist_cats` (
                                          `id` INT(11) NOT NULL AUTO_INCREMENT,
                                          `title` VARCHAR(100) NOT NULL,
                                          PRIMARY KEY (`id`)
                                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=1;');
                }

                // Add column catid if it doesn't already exist.
                if (!$this->db()->ifColumnExists('[prefix]_gamelist', 'catid')) {
                    $this->db()->query('ALTER TABLE `[prefix]_gamelist` ADD COLUMN `catid` INT(11) NOT NULL AFTER `id`;');
                }

                // Add column videourl if it doesn't already exist.
                if (!$this->db()->ifColumnExists('[prefix]_gamelist', 'videourl')) {
                    $this->db()->query('ALTER TABLE `[prefix]_gamelist` ADD COLUMN `videourl` VARCHAR(100) NOT NULL AFTER `title`;');
                }
            case "1.2.0":
            case "1.3.0":
            case "1.4.0":
                $this->db()->query('ALTER TABLE `[prefix]_gamelist_entrants` MODIFY COLUMN `user_id` INT(11) UNSIGNED NOT NULL;');
                $this->db()->query('ALTER TABLE `[prefix]_gamelist_entrants` ADD CONSTRAINT `[prefix]_gamelist_entrants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `[prefix]_users`(`id`) ON DELETE CASCADE;');
        }
    }
}
