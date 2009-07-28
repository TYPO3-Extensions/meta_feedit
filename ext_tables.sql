#
# Table structure for table 'tx_metaadminactions_actions'
#
CREATE TABLE tx_metafeedit_actions (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	sorting int(10) DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	image blob NOT NULL,
	url tinytext NOT NULL,
	page blob NOT NULL,
	tooltip text NOT NULL,
	category tinytext NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'tx_metafeedit_comments'
#

CREATE TABLE tx_metafeedit_comments (
	uid int(11) unsigned NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	linked_row blob NOT NULL,
	sys_language_uid varchar(50) DEFAULT '' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	firstname varchar(25) DEFAULT '' NOT NULL,
	surname varchar(255) DEFAULT '' NOT NULL,
	email varchar(50) DEFAULT '' NOT NULL,
	homepage text NOT NULL,
	place varchar(50) DEFAULT '' NOT NULL,
	entry text NOT NULL,
	entrycomment text NOT NULL,
	remote_addr varchar(50) DEFAULT '' NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_metafeedit_workflows (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_metafeedit_states (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	wid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	defaultstate tinyint(4) unsigned DEFAULT '0' NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE TABLE tx_metafeedit_transitions (
	uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
	pid int(11) unsigned DEFAULT '0' NOT NULL,
	wid int(11) unsigned DEFAULT '0' NOT NULL,
	startstateid int(11) unsigned DEFAULT '0' NOT NULL,
	endstateid int(11) unsigned DEFAULT '0' NOT NULL,
	tstamp int(11) unsigned DEFAULT '0' NOT NULL,
	crdate int(11) unsigned DEFAULT '0' NOT NULL,
	cruser_id int(11) unsigned DEFAULT '0' NOT NULL,
	deleted tinyint(4) unsigned DEFAULT '0' NOT NULL,
	hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
	starttime int(11) unsigned DEFAULT '0' NOT NULL,
	endtime int(11) unsigned DEFAULT '0' NOT NULL,
	title tinytext NOT NULL,
	PRIMARY KEY (uid),
	KEY parent (pid)
);