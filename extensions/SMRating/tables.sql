CREATE TABLE IF NOT EXISTS SMRating
(
	`pageid`				varchar(32)						DEFAULT NULL,
	`instanceid`			integer							DEFAULT NULL,
	`maxvalue`				integer							DEFAULT NULL,
	`count`					integer							DEFAULT NULL,
	`value`					float							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;
