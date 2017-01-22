CREATE TABLE IF NOT EXISTS SMMenu
(
	`id`					varchar(32)						DEFAULT NULL,
	`parent`				varchar(32)						DEFAULT NULL,
	`order`					integer							DEFAULT NULL,
	`title`					varchar(255)					DEFAULT NULL,
	`url`					varchar(255)					DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;
