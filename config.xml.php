<?php exit(); ?>
<?xml version="1.0" encoding="ISO-8859-1"?>
<entries>
	<!-- REQUIRED, throws custom exception if missing -->
	<entry key="Username" value="admin" />
	<!-- REQUIRED, throws custom exception if missing -->
	<entry key="Password" value="admin" />
	<!-- optional, may be left out or empty - defaults to 'en' -->
	<entry key="Language" value="en" />
	<!-- optional, may be left out or empty - defaults to empty collection -->
	<entry key="Languages" value="en;da;de;el;fr;sr" />
	<!-- optional, may be left out or empty - defaults to 'Default' -->
	<entry key="TemplatePublic" value="SMSunrise2017" />
	<!-- optional, may be left out or empty - defaults to 'Default' -->
	<entry key="TemplateAdmin" value="SMSunrise2017" />
	<!-- optional, may be left out or empty - defaults to False -->
	<entry key="AllowTemplateOverriding" value="False" />
	<!-- optional, may be left out or empty - no extension is loaded in this case (empty page) -->
	<entry key="DefaultExtension" value="SMPages" />
	<!-- REQUIRED, Sitemagic Framework won't execute extensions if left out or empty -->
	<entry key="ExtensionsEnabled" value="SMAnnouncements;SMAutoSeoUrls;SMConfig;SMExtensionCommon;SMLogin;SMPages;SMMenu;SMFiles;SMContact;SMExternalModules;SMDesigner;SMComments;SMRating;SMTips;SMSearch" />
	<!-- optional, may be left out (empty will prevent uploads) - by default no restrictions apply -->
	<!-- entry key="FileExtensionFilter" value="png;gif;jpg;jpeg;pdf;doc;docx;pages;xls;xlsx;numbers;ppt;pptx;keynote;key;zip;txt" / -->
	<!-- optional, may be left out or empty - defaults to server setting -->
	<entry key="DefaultTimeZoneOverride" value="" />
	<!-- optional, may be left out or empty - defaults to 'Default' -->
	<entry key="ImageTheme" value="Default" />
	<!-- optional, may be left out (null) or empty -->
	<entry key="LicenseKey" value="" />
	<!-- REQUIRED, but only if using MySQL as the data source -->
	<entry key="DatabaseConnection" value="localhost;db;user;pass" />
	<!-- optional, may be left out or empty - defaults to False -->
	<entry key="Debug" value="False" />

	<!-- optional, may be left out or empty (in which case PHP mail() is used to send e-mails) -->
	<entry key="SMTPHost" value="" />
	<!-- optional, may be left out or empty - defaults to 25) -->
	<entry key="SMTPPort" value="" />
	<!-- optional, may be left out or empty - valid values: LOGIN (default), PLAIN, NTLM, CRAM-MD5 -->
	<entry key="SMTPAuthType" value="" />
	<!-- optional, may be left out or empty - valid values: TLS, SSL -->
	<entry key="SMTPEncryption" value="" />
	<!-- optional, may be left out or empty -->
	<entry key="SMTPUser" value="" />
	<!-- optional, may be left out or empty -->
	<entry key="SMTPPass" value="" />
	<!-- optional, may be left out or empty -->
	<entry key="SMTPSender" value="" />
	<!-- optional, may be left out or empty - separate multiple headers by line break (\n) -->
	<entry key="SMTPHeaders" value="" />
	<!-- optional, may be left out or empty - e.g. my-domain.com -->
	<entry key="SMTPDKIMDomain" value="" />
	<!-- optional, may be left out or empty - e.g. keys/.privatekey -->
	<entry key="SMTPDKIMPrivateKeyPath" value="" />
	<!-- optional, may be left out or empty - e.g. phpmailer -->
	<entry key="SMTPDKIMSelector" value="" />
	<!-- optional, may be left out or empty - defaults to False -->
	<entry key="SMTPAllowSelfSignedSSL" value="False" />
	<!-- optional, may be left out or empty - defaults to False -->
	<entry key="SMTPDebug" value="False" />
	<!-- optional, may be left out or empty - variable support: %user = $_SERVER["USER"], %hostname = $_SERVER["SERVER_NAME"] -->
	<entry key="SMTPFallbackSender" value=""/>

	<!-- optional, may be left out or empty - defaults to 'stable' - possible values are: stable, dev (NOT for production sites!) -->
	<entry key="UpgradeMode" value="stable"/>
	<!-- optional, may be left out or empty - defaults to False -->
	<entry key="CloudMode" value="False"/>
</entries>