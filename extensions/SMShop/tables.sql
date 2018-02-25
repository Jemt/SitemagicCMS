/* SMShop */

CREATE TABLE IF NOT EXISTS SMShopProducts
(
	`id`					varchar(240)						DEFAULT NULL,
	`category`				text								DEFAULT NULL,
	`categoryid`			varchar(70)							DEFAULT NULL,
	`title`					text								DEFAULT NULL,
	`description`			text								DEFAULT NULL,
	`images`				text								DEFAULT NULL,
	`price`					decimal(15,5)						DEFAULT NULL,
	`vat`					decimal(15,5)						DEFAULT NULL,
	`currency`				varchar(3)							DEFAULT NULL,
	`weight`				decimal(15,5)						DEFAULT NULL,
	`weightunit`			varchar(3)							DEFAULT NULL,
	`deliverytime`			text								DEFAULT NULL,
	`discountexpression`	varchar(250)						DEFAULT NULL,
	`discountmessage`		text								DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS SMShopOrders
(
	`id`					varchar(50)							DEFAULT NULL,
	`time`					bigint								DEFAULT NULL,
	`clientip`				varchar(45)							DEFAULT NULL,
	`company`				text								DEFAULT NULL,
	`firstname`				text								DEFAULT NULL,
	`lastname`				text								DEFAULT NULL,
	`address`				text								DEFAULT NULL,
	`zipcode`				varchar(20)							DEFAULT NULL,
	`city`					text								DEFAULT NULL,
	`email`					text								DEFAULT NULL,
	`phone`					varchar(20)							DEFAULT NULL,
	`message`				text								DEFAULT NULL,
	`altcompany`			text								DEFAULT NULL,
	`altfirstname`			text								DEFAULT NULL,
	`altlastname`			text								DEFAULT NULL,
	`altaddress`			text								DEFAULT NULL,
	`altzipcode`			varchar(20)							DEFAULT NULL,
	`altcity`				text								DEFAULT NULL,
	`price`					decimal(15,5)						DEFAULT NULL,
	`vat`					decimal(15,5)						DEFAULT NULL,
	`currency`				varchar(3)							DEFAULT NULL,
	`weight`				decimal(15,5)						DEFAULT NULL,
	`weightunit`			varchar(3)							DEFAULT NULL,
	`costcorrection1`		decimal(15,5)						DEFAULT NULL,
	`costcorrectionvat1`	decimal(15,5)						DEFAULT NULL,
	`costcorrectionmessage1`text								DEFAULT NULL,
	`costcorrection2`		decimal(15,5)						DEFAULT NULL,
	`costcorrectionvat2`	decimal(15,5)						DEFAULT NULL,
	`costcorrectionmessage2`text								DEFAULT NULL,
	`costcorrection3`		decimal(15,5)						DEFAULT NULL,
	`costcorrectionvat3`	decimal(15,5)						DEFAULT NULL,
	`costcorrectionmessage3`text								DEFAULT NULL,
	`paymentmethod`			varchar(50)							DEFAULT NULL,
	`transactionid`			varchar(100)						DEFAULT NULL,
	`state`					varchar(20)							DEFAULT NULL,
	`promocode`				text								DEFAULT NULL,
	`custdata1`				text								DEFAULT NULL,
	`custdata2`				text								DEFAULT NULL,
	`custdata3`				text								DEFAULT NULL,
	`invoiceid`				varchar(50)							DEFAULT NULL, /* Why not int ?? */
	`invoicetime`			bigint								DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;

CREATE TABLE IF NOT EXISTS SMShopOrderEntries
(
	`id`					varchar(50)							DEFAULT NULL,
	`orderid`				varchar(50)							DEFAULT NULL,
	`productid`				varchar(240)						DEFAULT NULL,
	`unitprice`				decimal(15,5)						DEFAULT NULL,
	`vat`					decimal(15,5)						DEFAULT NULL,
	`currency`				varchar(3)							DEFAULT NULL,
	`units`					bigint								DEFAULT NULL, /* Why not int ?? */
	`discount`				decimal(15,5)						DEFAULT NULL,
	`discountmessage`		text								DEFAULT NULL
) ENGINE = InnoDB CHARACTER SET latin1 COLLATE latin1_swedish_ci;
