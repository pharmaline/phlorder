#
# Die Systemspalten (uid, pid, tstamp, crdate, deleted, hidden, starttime, endtime,
# sys_language_uid, l10n_parent, l10n_diffsource, t3ver_*) sowie PRIMARY KEY und die
# Standard-Indizes legt der Core in v13 selbst aus der TCA-"ctrl" an (DefaultTcaSchema).
# Hier stehen deshalb nur noch die Fachspalten.
# cruser_id ist in v12 entfernt worden und deshalb ersatzlos raus.
#

#
# Table structure for table 'tx_phlorder_domain_model_order'
#
CREATE TABLE tx_phlorder_domain_model_order (
	phluserfid int(11) DEFAULT '0' NOT NULL,
	feusefrid int(11) DEFAULT '0' NOT NULL,
	orderid varchar(255) DEFAULT '' NOT NULL,
	ordernumber varchar(20) DEFAULT '' NOT NULL,
	timestamp datetime DEFAULT NULL,
	status varchar(255) DEFAULT '' NOT NULL,
	company varchar(255) DEFAULT '' NOT NULL,
	salutation varchar(255) DEFAULT '' NOT NULL,
	last_name varchar(255) DEFAULT '' NOT NULL,
	first_name varchar(255) DEFAULT '' NOT NULL,
	address varchar(255) DEFAULT '' NOT NULL,
	zip varchar(255) DEFAULT '' NOT NULL,
	city varchar(255) DEFAULT '' NOT NULL,
	phone varchar(255) DEFAULT '' NOT NULL,
	email varchar(255) DEFAULT '' NOT NULL,
	mobil varchar(255) DEFAULT '' NOT NULL,
	delivery varchar(255) DEFAULT '' NOT NULL,
	payment varchar(255) DEFAULT '' NOT NULL,
	i_b_a_n varchar(255) DEFAULT '' NOT NULL,
	order_image int(11) unsigned DEFAULT '0' NOT NULL,
	note varchar(255) DEFAULT '' NOT NULL,
	tolog int(11) unsigned DEFAULT '0' NOT NULL,
	toitem int(11) unsigned DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_phlorder_domain_model_log'
#
CREATE TABLE tx_phlorder_domain_model_log (
	tx_order int(11) unsigned DEFAULT '0' NOT NULL,
	timestamp datetime DEFAULT NULL,
	action varchar(255) DEFAULT '' NOT NULL,
	result varchar(255) DEFAULT '' NOT NULL,
	value1 varchar(255) DEFAULT '' NOT NULL,
	value2 varchar(255) DEFAULT '' NOT NULL,
	free varchar(255) DEFAULT '' NOT NULL,
	actor varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_phlorder_domain_model_item'
#
CREATE TABLE tx_phlorder_domain_model_item (
	tx_order int(11) unsigned DEFAULT '0' NOT NULL,
	pzn varchar(255) DEFAULT '' NOT NULL,
	name varchar(255) DEFAULT '' NOT NULL,
	size varchar(255) DEFAULT '' NOT NULL,
	dar varchar(255) DEFAULT '' NOT NULL,
	qty varchar(255) DEFAULT '' NOT NULL,
	price varchar(255) DEFAULT '' NOT NULL,
	diff varchar(255) DEFAULT '' NOT NULL,
	imgfid varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_phlorder_domain_model_token'
#
CREATE TABLE tx_phlorder_domain_model_token (
	phluserfid int(11) DEFAULT '0' NOT NULL,
	token varchar(255) DEFAULT '' NOT NULL,
	timestamp varchar(255) DEFAULT '' NOT NULL,
	status varchar(255) DEFAULT '' NOT NULL,
	free varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_phlorder_domain_model_fastorder'
#
# ACHTUNG: Diese Tabelle hat weder TCA noch Extbase-Modell noch Code in dieser
# Extension - der Core kann hier also NICHTS ergaenzen. Deshalb bleiben uid, pid,
# PRIMARY KEY und die Enable-Spalten hier bewusst vollstaendig stehen.
# Sie enthaelt 18 produktive Zeilen (Stand Phase 2), darum nicht entfernen, bis
# geklaert ist, welches System sie schreibt und liest.
#
CREATE TABLE tx_phlorder_domain_model_fastorder (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	phluserid int(11) DEFAULT '0' NOT NULL,
	faorid varchar(255) DEFAULT '' NOT NULL,
	password varchar(255) DEFAULT '' NOT NULL,
	token varchar(255) DEFAULT '' NOT NULL,
	regdate datetime DEFAULT NULL,
	timestamp varchar(255) DEFAULT '' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

# Entfernt (Phase 2): vier CREATE-TABLE-Bloecke ohne jede Spalte -
# tx_phlorder_domain_model_fastorder_data, ..._fastorder_token, ..._feedback,
# ..._sepa_lastschrift. Der Schema-Analyzer von v13 bricht darauf ab
# ("No columns specified for table"). Keine der vier Tabellen existierte in der
# Datenbank, es gehen also keine Daten verloren.
