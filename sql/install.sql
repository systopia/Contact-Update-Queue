DROP TABLE IF EXISTS i3val_session_cache;

CREATE TABLE i3val_session_cache (
     `id`          int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'ID',
     `session_key` varchar(40)  NOT NULL COMMENT 'Session key',
     `activity_id` int unsigned NOT NULL COMMENT 'Claimed activity_id',
     `expires`     datetime     NOT NULL COMMENT 'Entry is valid until',
    PRIMARY KEY ( `id` ),
    INDEX `expiration`(expires),
    UNIQUE INDEX `next` (session_key, activity_id)
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
