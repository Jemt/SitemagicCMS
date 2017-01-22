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
