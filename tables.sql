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
	`id`					varchar(32)						DEFAULT NULL,
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

/* SMShop */

CREATE TABLE IF NOT EXISTS SMShopState
(
	`key`					varchar(255)					DEFAULT NULL,
	`value`					varchar(255)					DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS SMShopProducts
(
	`id`					varchar(240)					DEFAULT NULL,
	`category`				text							DEFAULT NULL,
	`categoryid`			varchar(70)						DEFAULT NULL,
	`title`					text							DEFAULT NULL,
	`description`			text							DEFAULT NULL,
	`images`				text							DEFAULT NULL,
	`price`					decimal(15,5)					DEFAULT NULL, /* Max value 9999999999.99999 - always with 5 decimal precision (e.g. 100.00000) */
	`vat`					decimal(15,5)					DEFAULT NULL,
	`currency`				varchar(3)						DEFAULT NULL,
	`weight`				decimal(15,5)					DEFAULT NULL,
	`weightunit`			varchar(3)						DEFAULT NULL,
	`deliverytime`			text							DEFAULT NULL,
	`discountexpression`	varchar(250)					DEFAULT NULL,
	`discountmessage`		text							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS SMShopOrders
(
	`id`					varchar(50)						DEFAULT NULL,
	`time`					bigint							DEFAULT NULL,
	`clientip`				varchar(45)						DEFAULT NULL,
	`company`				text							DEFAULT NULL,
	`firstname`				text							DEFAULT NULL,
	`lastname`				text							DEFAULT NULL,
	`address`				text							DEFAULT NULL,
	`zipcode`				varchar(20)						DEFAULT NULL,
	`city`					text							DEFAULT NULL,
	`email`					text							DEFAULT NULL,
	`phone`					varchar(20)						DEFAULT NULL,
	`message`				text							DEFAULT NULL,
	`altcompany`			text							DEFAULT NULL,
	`altfirstname`			text							DEFAULT NULL,
	`altlastname`			text							DEFAULT NULL,
	`altaddress`			text							DEFAULT NULL,
	`altzipcode`			varchar(20)						DEFAULT NULL,
	`altcity`				text							DEFAULT NULL,
	`price`					decimal(15,5)					DEFAULT NULL,
	`vat`					decimal(15,5)					DEFAULT NULL,
	`currency`				varchar(3)						DEFAULT NULL,
	`weight`				decimal(15,5)					DEFAULT NULL,
	`weightunit`			varchar(3)						DEFAULT NULL,
	`costcorrection1`		decimal(15,5)					DEFAULT NULL,
	`costcorrectionvat1`	decimal(15,5)					DEFAULT NULL,
	`costcorrectionmessage1`text							DEFAULT NULL,
	`costcorrection2`		decimal(15,5)					DEFAULT NULL,
	`costcorrectionvat2`	decimal(15,5)					DEFAULT NULL,
	`costcorrectionmessage2`text							DEFAULT NULL,
	`costcorrection3`		decimal(15,5)					DEFAULT NULL,
	`costcorrectionvat3`	decimal(15,5)					DEFAULT NULL,
	`costcorrectionmessage3`text							DEFAULT NULL,
	`paymentmethod`			varchar(50)						DEFAULT NULL,
	`transactionid`			varchar(100)					DEFAULT NULL,
	`state`					varchar(20)						DEFAULT NULL,
	`promocode`				varchar(240)					DEFAULT NULL,
	`custdata1`				text							DEFAULT NULL,
	`custdata2`				text							DEFAULT NULL,
	`custdata3`				text							DEFAULT NULL,
	`invoiceid`				varchar(50)						DEFAULT NULL,
	`invoicetime`			bigint							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS SMShopOrderEntries
(
	`id`					varchar(50)						DEFAULT NULL,
	`orderid`				varchar(50)						DEFAULT NULL,
	`productid`				varchar(240)					DEFAULT NULL,
	`unitprice`				decimal(15,5)					DEFAULT NULL,
	`vat`					decimal(15,5)					DEFAULT NULL,
	`currency`				varchar(3)						DEFAULT NULL,
	`units`					int unsigned					DEFAULT NULL,
	`discount`				decimal(15,5)					DEFAULT NULL,
	`discountmessage`		text							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;


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
