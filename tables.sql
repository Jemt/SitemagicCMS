/* ================================================================================== */
/* Create tables */
/* ================================================================================== */

/* Base */

CREATE TABLE IF NOT EXISTS SMAttributes
(
	`key`					varchar(255)					DEFAULT NULL,
	`value`					varchar(255)					DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS SMLog
(
	`datetime`				datetime						DEFAULT NULL,
	`file`					varchar(255)					DEFAULT NULL,
	`line`					integer							DEFAULT NULL,
	`message`				text							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

/* SMComments */

CREATE TABLE IF NOT EXISTS SMComments
(
	`pageid`				varchar(32)						DEFAULT NULL,
	`instanceid`			integer							DEFAULT NULL,
	`commentid`				varchar(32)						DEFAULT NULL,
	`name`					varchar(255)					DEFAULT NULL,
	`comment`				text							DEFAULT NULL,
	`timestamp`				integer							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

/* SMContact */

CREATE TABLE IF NOT EXISTS SMContact
(
	`id`					varchar(39)						DEFAULT NULL, /* e.g. CONF10_GUID = 39 chars */
	`title`					varchar(255)					DEFAULT NULL,
	`type`					varchar(10)						DEFAULT NULL,
	`position`				integer							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

/* SMDownloads */

CREATE TABLE IF NOT EXISTS SMDownloads
(
	`file`					varchar(255)					DEFAULT NULL,
	`count`					integer							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

/* SMExternal Modules */

CREATE TABLE IF NOT EXISTS SMExternalModules
(
	`guid`					varchar(32)						DEFAULT NULL,
	`name`					varchar(255)					DEFAULT NULL,
	`url`					text							DEFAULT NULL,
	`width`					varchar(5)						DEFAULT NULL,
	`widthunit`				varchar(7)						DEFAULT NULL,
	`height`				varchar(5)						DEFAULT NULL,
	`heightunit`			varchar(7)						DEFAULT NULL,
	`scroll`				varchar(4)						DEFAULT NULL,
	`reloadtotop`			varchar(5)						DEFAULT NULL,
	`framecolor`			varchar(7)						DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

/* SMMenu */

CREATE TABLE IF NOT EXISTS SMMenu
(
	`id`					varchar(32)						DEFAULT NULL,
	`parent`				varchar(32)						DEFAULT NULL,
	`order`					integer							DEFAULT NULL,
	`title`					varchar(255)					DEFAULT NULL,
	`url`					varchar(255)					DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

/* SMPages */

CREATE TABLE IF NOT EXISTS SMPages
(
	`guid`					varchar(32)						DEFAULT NULL,
	`filename`				varchar(255)					DEFAULT NULL,
	`title`					varchar(255)					DEFAULT NULL,
	`content`				text							DEFAULT NULL,
	`accessible`			varchar(5)						DEFAULT NULL,
	`template`				varchar(255)					DEFAULT NULL,
	`keywords`				varchar(255)					DEFAULT NULL,
	`description`			varchar(255)					DEFAULT NULL,
	`allowindexing`			varchar(5)						DEFAULT NULL,
	`password`				varchar(255)					DEFAULT NULL,
	`lastmodified`			integer							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

/* SMRating */

CREATE TABLE IF NOT EXISTS SMRating
(
	`pageid`				varchar(32)						DEFAULT NULL,
	`instanceid`			integer							DEFAULT NULL,
	`maxvalue`				integer							DEFAULT NULL,
	`count`					integer							DEFAULT NULL,
	`value`					float							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;


/* ================================================================================== */
/* Create functions */
/* ================================================================================== */

/* Credits: https://stackoverflow.com/a/36343632 */
DROP FUNCTION IF EXISTS sm_entity_decode;
DELIMITER $$
	CREATE FUNCTION sm_entity_decode(txt TEXT CHARSET utf8) RETURNS TEXT CHARSET utf8
	NO SQL
	DETERMINISTIC
	BEGIN

	DECLARE tmp			TEXT CHARSET utf8 DEFAULT txt;
	DECLARE entity		TEXT CHARSET utf8;
	DECLARE pos1		INT DEFAULT 1;
	DECLARE pos2		INT;
	DECLARE codepoint	INT;

	IF txt IS NULL THEN
		RETURN NULL;
	END IF;
	LOOP
		SET pos1 = LOCATE('&#', tmp, pos1);
		IF pos1 = 0 THEN
			RETURN tmp;
		END IF;
		SET pos2 = LOCATE(';', tmp, pos1 + 2);
		IF pos2 > pos1 THEN
			SET entity = SUBSTRING(tmp, pos1, pos2 - pos1 + 1);
			IF entity REGEXP '^&#[[:digit:]]+;$' THEN
				SET codepoint = CAST(SUBSTRING(entity, 3, pos2 - pos1 - 2) AS UNSIGNED);
				IF codepoint > 31 THEN
					SET tmp = CONCAT(LEFT(tmp, pos1 - 1), CHAR(codepoint USING utf32), SUBSTRING(tmp, pos2 + 1));
				END IF;
			END IF;
			IF entity REGEXP '^&#x[[:digit:]]+;$' THEN
				SET codepoint = CAST(CONV(SUBSTRING(entity, 4, pos2 - pos1 - 3), 16, 10) AS UNSIGNED);
				IF codepoint > 31 THEN
					SET tmp = CONCAT(LEFT(tmp, pos1 - 1), CHAR(codepoint USING utf32), SUBSTRING(tmp, pos2 + 1));
				END IF;
			END IF;
		END IF;
		SET pos1 = pos1 + 1;
	END LOOP;
END$$
DELIMITER ;


/* ================================================================================== */
/* Upgrade procedures */
/* ================================================================================== */

/* Ensure template attribute in SMPages (added in Sitemagic CMS 2014) */
DELIMITER ;;
CREATE PROCEDURE UpgradeSMPages()
BEGIN
	DECLARE CONTINUE HANDLER for 1060 BEGIN END;
	ALTER TABLE SMPages ADD `template` VARCHAR(250) DEFAULT NULL AFTER `accessible`;
END;;
CALL UpgradeSMPages();;
DELIMITER ;
DROP PROCEDURE UpgradeSMPages;

/* Increase length of various fields (changed in Sitemagic CMS 2014) */
ALTER TABLE SMAttributes MODIFY `key` VARCHAR(255);
ALTER TABLE SMAttributes MODIFY `value` VARCHAR(255);
ALTER TABLE SMLog MODIFY `file` VARCHAR(255);
ALTER TABLE SMComments MODIFY `name` VARCHAR(255);
ALTER TABLE SMContact MODIFY `title` VARCHAR(255);
ALTER TABLE SMDownloads MODIFY `file` VARCHAR(255);
ALTER TABLE SMExternalModules MODIFY `name` VARCHAR(255);
ALTER TABLE SMMenu MODIFY `title` VARCHAR(255);
ALTER TABLE SMMenu MODIFY `url` VARCHAR(255);
ALTER TABLE SMPages MODIFY `filename` VARCHAR(255);
ALTER TABLE SMPages MODIFY `title` VARCHAR(255);
ALTER TABLE SMPages MODIFY `template` VARCHAR(255);
ALTER TABLE SMPages MODIFY `keywords` VARCHAR(255);
ALTER TABLE SMPages MODIFY `description` VARCHAR(255);
ALTER TABLE SMPages MODIFY `password` VARCHAR(255);
