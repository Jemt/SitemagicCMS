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
