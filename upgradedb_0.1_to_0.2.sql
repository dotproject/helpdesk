ALTER TABLE `helpdesk_items` ADD `item_requestor_phone` varchar(30) NOT NULL default '' AFTER `item_requestor_email`;
ALTER TABLE `helpdesk_items` ADD `item_company_id` int(11) NOT NULL default '0' AFTER `item_project_id`;
