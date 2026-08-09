SET NAMES utf8mb4;

DROP TABLE IF EXISTS `votes`;
DROP TABLE IF EXISTS `places`;

CREATE TABLE `places` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `city`       VARCHAR(100) NOT NULL,
    `name`       VARCHAR(150) NOT NULL,
    `active`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_place` (`city`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `votes` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `place_id`    INT UNSIGNED NOT NULL,
    `ip_address`  VARCHAR(45)  NOT NULL,
    `vote`        ENUM('working','not_working') NOT NULL,
    `slot_date`   DATE         NOT NULL,
    `slot_number` TINYINT UNSIGNED NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_vote` (`ip_address`,`place_id`,`slot_date`,`slot_number`),
    KEY `idx_slot`  (`slot_date`,`slot_number`),
    KEY `idx_place` (`place_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `places` (`city`,`name`) VALUES
('Tunis','Bab Bhar (Ville Nouvelle)'),('Tunis','Bab Souika'),('Tunis','Bab El Khadra'),
('Tunis','Bab El Fellah'),('Tunis','Medina de Tunis'),('Tunis','Le Bardo'),
('Tunis','La Marsa'),('Tunis','Carthage'),('Tunis','Sidi Bou Said'),
('Tunis','La Goulette'),('Tunis','Le Kram'),('Tunis','Ain Zaghouan'),
('Tunis','Cite Olympique'),('Tunis','Cite Ettadhamen'),('Tunis','Cite El Khadra'),
('Tunis','Cite Ennasr'),('Tunis','Cite Ghazela'),('Tunis','Cite Jardins'),
('Tunis','Cite Mahrajene'),('Tunis','Cite Montplaisir'),('Tunis','Cite Sportive'),
('Tunis','El Menzah 1'),('Tunis','El Menzah 2'),('Tunis','El Menzah 3'),
('Tunis','El Menzah 4'),('Tunis','El Menzah 5'),('Tunis','El Menzah 6'),
('Tunis','El Menzah 7'),('Tunis','El Menzah 8'),('Tunis','El Menzah 9'),
('Tunis','Aouina'),('Tunis','El Manar 1'),('Tunis','El Manar 2'),('Tunis','El Manar 3'),
('Tunis','El Omrane'),('Tunis','El Omrane Superieur'),('Tunis','El Ouardia'),
('Tunis','Ezzouhour'),('Tunis','Hrairia'),('Tunis','Jebel Jelloud'),
('Tunis','Kabaria'),('Tunis','Mellassine'),('Tunis','Sejoumi'),
('Tunis','Sidi El Bechir'),('Tunis','Sidi Hassine'),
('Tunis','Hay El Khadra'),('Tunis','Hay El Warraq'),('Tunis','Hay Hlel');

INSERT INTO `places` (`city`,`name`) VALUES
('Ariana','Ariana Ville'),('Ariana','Ettadhamen'),('Ariana','Mnihla'),
('Ariana','Kalaat el-Andalous'),('Ariana','La Soukra'),('Ariana','Raoued'),
('Ariana','Sidi Thabet'),('Ariana','Borj Louzir'),('Ariana','Cite Ennasr 1'),
('Ariana','Cite Ennasr 2'),('Ariana','Ghazela'),('Ariana','Jardins El Menzah'),
('Ariana','Borj Turki'),('Ariana','Chotrana'),('Ariana','Dar Fadhal');

INSERT INTO `places` (`city`,`name`) VALUES
('Ben Arous','Ben Arous Ville'),('Ben Arous','Bou Mhel el-Bassatine'),
('Ben Arous','El Mourouj'),('Ben Arous','Ezzahra'),('Ben Arous','Fouchana'),
('Ben Arous','Hammam Chott'),('Ben Arous','Hammam Lif'),('Ben Arous','Khalidia'),
('Ben Arous','Medina Jedida'),('Ben Arous','Megrine'),('Ben Arous','Mornag'),
('Ben Arous','Mohamedia'),('Ben Arous','Nouvelle Medina'),('Ben Arous','Rades'),
('Ben Arous','Sidi Rezig');

INSERT INTO `places` (`city`,`name`) VALUES
('Manouba','Manouba Ville'),('Manouba','Borj El Amri'),('Manouba','Djedeida'),
('Manouba','El Battan'),('Manouba','Mornaguia'),('Manouba','Oued Ellil'),
('Manouba','Tebourba'),('Manouba','Douar Hicher');

INSERT INTO `places` (`city`,`name`) VALUES
('Nabeul','Nabeul Ville'),('Nabeul','Hammamet'),('Nabeul','Kelibia'),
('Nabeul','Korba'),('Nabeul','Menzel Bouzelfa'),('Nabeul','Menzel Temime'),
('Nabeul','Soliman'),('Nabeul','Takelsa'),('Nabeul','Beni Khalled'),
('Nabeul','Beni Khiar'),('Nabeul','Dar Chaabane'),('Nabeul','El Haouaria'),
('Nabeul','Grombalia'),('Nabeul','Hammam Ghezaz'),('Nabeul','Korbous'),
('Nabeul','Maamoura'),('Nabeul','Menzel Horr'),('Nabeul','Somaa'),('Nabeul','Tazerka');

INSERT INTO `places` (`city`,`name`) VALUES
('Zaghouan','Zaghouan Ville'),('Zaghouan','Bir Mcherga'),('Zaghouan','El Fahs'),
('Zaghouan','Nadhour'),('Zaghouan','Saouaf'),('Zaghouan','Zriba');

INSERT INTO `places` (`city`,`name`) VALUES
('Bizerte','Bizerte Ville'),('Bizerte','El Alia'),('Bizerte','Ghar El Melh'),
('Bizerte','Ghezala'),('Bizerte','Joumine'),('Bizerte','Mateur'),
('Bizerte','Menzel Bourguiba'),('Bizerte','Menzel Jemil'),('Bizerte','Ras Jebel'),
('Bizerte','Sejnane'),('Bizerte','Tinja'),('Bizerte','Utique'),('Bizerte','Zarzouna');

INSERT INTO `places` (`city`,`name`) VALUES
('Beja','Beja Ville'),('Beja','Amdoun'),('Beja','Goubellat'),
('Beja','Medjez el-Bab'),('Beja','Nefza'),('Beja','Slouguia'),
('Beja','Teboursouk'),('Beja','Testour'),('Beja','Thibar');

INSERT INTO `places` (`city`,`name`) VALUES
('Jendouba','Jendouba Ville'),('Jendouba','Ain Draham'),('Jendouba','Balta-Bou Aouane'),
('Jendouba','Bou Salem'),('Jendouba','Fernana'),('Jendouba','Ghardimaou'),
('Jendouba','Oued Mliz'),('Jendouba','Tabarka');

INSERT INTO `places` (`city`,`name`) VALUES
('Kef','Le Kef Ville'),('Kef','Dahmani'),('Kef','Es Sers'),('Kef','Jerissa'),
('Kef','Kalaat Sinane'),('Kef','Kalaat Khasba'),('Kef','Nebeur'),
('Kef','Sakiet Sidi Youssef'),('Kef','Tajerouine');

INSERT INTO `places` (`city`,`name`) VALUES
('Siliana','Siliana Ville'),('Siliana','Bargou'),('Siliana','Bou Arada'),
('Siliana','El Aroussa'),('Siliana','El Krib'),('Siliana','Gaafour'),
('Siliana','Kesra'),('Siliana','Makthar'),('Siliana','Rohia'),('Siliana','Sidi Bou Rouis');

INSERT INTO `places` (`city`,`name`) VALUES
('Sousse','Sousse Ville'),('Sousse','Akouda'),('Sousse','Bouficha'),
('Sousse','Enfidha'),('Sousse','Hammam Sousse'),('Sousse','Hergla'),
('Sousse','Kalaa Kebira'),('Sousse','Kalaa Seghira'),('Sousse','Kondar'),
('Sousse','Msaken'),('Sousse','Sidi Bou Ali'),('Sousse','Sidi El Hani'),
('Sousse','Zaouiet Sousse');

INSERT INTO `places` (`city`,`name`) VALUES
('Monastir','Monastir Ville'),('Monastir','Bembla'),('Monastir','Beni Hassen'),
('Monastir','Jammel'),('Monastir','Ksar Hellal'),('Monastir','Ksibet el-Mediouni'),
('Monastir','Moknine'),('Monastir','Ouerdanine'),('Monastir','Sahline'),
('Monastir','Sayada-Lamta-Bou Hajar'),('Monastir','Teboulba'),('Monastir','Zeramdine');

INSERT INTO `places` (`city`,`name`) VALUES
('Mahdia','Mahdia Ville'),('Mahdia','Bou Merdes'),('Mahdia','Chebba'),
('Mahdia','Chorbane'),('Mahdia','El Bradaa'),('Mahdia','Essouassi'),
('Mahdia','Hebira'),('Mahdia','Ksour Essef'),('Mahdia','Melloulech'),
('Mahdia','Ouled Chamekh'),('Mahdia','Sidi Alouane');

INSERT INTO `places` (`city`,`name`) VALUES
('Sfax','Sfax Ville'),('Sfax','Agareb'),('Sfax','Bir Ali Ben Khalifa'),
('Sfax','Djebeniana'),('Sfax','El Amra'),('Sfax','El Ghraiba'),
('Sfax','Graiba'),('Sfax','Hencha'),('Sfax','Kerkennah'),('Sfax','Mahres'),
('Sfax','Menzel Chaker'),('Sfax','Sakiet Eddaier'),('Sfax','Sakiet Ezzit'),
('Sfax','Skhira'),('Sfax','Thyna');

INSERT INTO `places` (`city`,`name`) VALUES
('Kairouan','Kairouan Ville'),('Kairouan','Bou Hajla'),('Kairouan','Chebika'),
('Kairouan','Cherarda'),('Kairouan','El Alaa'),('Kairouan','Haffouz'),
('Kairouan','Hajeb El Aioun'),('Kairouan','Nasrallah'),('Kairouan','Oueslatia'),
('Kairouan','Sbikha');

INSERT INTO `places` (`city`,`name`) VALUES
('Kasserine','Kasserine Ville'),('Kasserine','Ain Jedey'),('Kasserine','El Ayoun'),
('Kasserine','Ezzouhour'),('Kasserine','Feriana'),('Kasserine','Foussana'),
('Kasserine','Hassi El Ferid'),('Kasserine','Hidra'),('Kasserine','Jedeliane'),
('Kasserine','Majel Bel Abbes'),('Kasserine','Sbeitla'),('Kasserine','Sbiba'),
('Kasserine','Thala');

INSERT INTO `places` (`city`,`name`) VALUES
('Sidi Bouzid','Sidi Bouzid Ville'),('Sidi Bouzid','Ben Oun'),
('Sidi Bouzid','Bir El Hafey'),('Sidi Bouzid','Cebbala Ouled Asker'),
('Sidi Bouzid','Jilma'),('Sidi Bouzid','Mazzouna'),('Sidi Bouzid','Meknassy'),
('Sidi Bouzid','Menzel Bouzaiane'),('Sidi Bouzid','Ouled Haffouz'),
('Sidi Bouzid','Regueb'),('Sidi Bouzid','Sidi Ali Ben Aoun'),('Sidi Bouzid','Souk Jedid');

INSERT INTO `places` (`city`,`name`) VALUES
('Gabes','Gabes Ville'),('Gabes','El Hamma'),('Gabes','Ghannouch'),
('Gabes','Mareth'),('Gabes','Matmata'),('Gabes','Matmata Nouvelle'),
('Gabes','Menzel El Habib'),('Gabes','Metouia'),('Gabes','Oudhref'),('Gabes','Zarat');

INSERT INTO `places` (`city`,`name`) VALUES
('Medenine','Medenine Ville'),('Medenine','Ben Gardane'),('Medenine','Beni Khedache'),
('Medenine','Djerba - Ajim'),('Medenine','Djerba - Houmt Souk'),
('Medenine','Djerba - Midoun'),('Medenine','Sidi Makhlouf'),('Medenine','Zarzis');

INSERT INTO `places` (`city`,`name`) VALUES
('Tataouine','Tataouine Ville'),('Tataouine','Bir Lahmar'),('Tataouine','Dehiba'),
('Tataouine','Ghomrassen'),('Tataouine','Remada'),('Tataouine','Smar');

INSERT INTO `places` (`city`,`name`) VALUES
('Gafsa','Gafsa Ville'),('Gafsa','Belkhir'),('Gafsa','El Guettar'),
('Gafsa','El Ksar'),('Gafsa','Mdhilla'),('Gafsa','Metlaoui'),
('Gafsa','Moulares'),('Gafsa','Redeyef'),('Gafsa','Sened'),('Gafsa','Sidi Aich');

INSERT INTO `places` (`city`,`name`) VALUES
('Tozeur','Tozeur Ville'),('Tozeur','Degache'),('Tozeur','Hazoua'),
('Tozeur','Nefta'),('Tozeur','Tameghza');

INSERT INTO `places` (`city`,`name`) VALUES
('Kebili','Kebili Ville'),('Kebili','Douz'),('Kebili','El Faouar'),('Kebili','Souk Lahad');

SELECT CONCAT(COUNT(*), ' places inserted') AS status FROM `places`;
