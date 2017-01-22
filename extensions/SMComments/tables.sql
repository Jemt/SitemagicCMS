CREATE TABLE IF NOT EXISTS SMComments
(
	`pageid`				varchar(32)						DEFAULT NULL,
	`instanceid`			integer							DEFAULT NULL,
	`commentid`				varchar(32)						DEFAULT NULL,
	`name`					varchar(255)					DEFAULT NULL,
	`comment`				text							DEFAULT NULL,
	`timestamp`				integer							DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;
