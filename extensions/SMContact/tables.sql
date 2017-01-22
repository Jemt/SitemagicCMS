CREATE TABLE IF NOT EXISTS SMContact
(
	`id`					varchar(32)						DEFAULT NULL,
	`title`					varchar(255)					DEFAULT NULL,
	`type`					varchar(9)						DEFAULT NULL,
	`position`				integer							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;
