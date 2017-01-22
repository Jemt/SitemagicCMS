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
