UPDATE system_settings SET db_schema_version='1318',version='2.6b0.5',db_schema_update_date=NOW() where db_schema_version < 1318;

ALTER TABLE vicidial_phone_codes MODIFY geographic_description VARCHAR(100);

ALTER TABLE vicidial_campaigns ADD in_group_dial ENUM('DISABLED','MANUAL_DIAL','NO_DIAL','BOTH') default 'DISABLED';
ALTER TABLE vicidial_campaigns ADD in_group_dial_select ENUM('AGENT_SELECTED','CAMPAIGN_SELECTED','ALL_USER_GROUP') default 'CAMPAIGN_SELECTED';

UPDATE system_settings SET db_schema_version='1319',db_schema_update_date=NOW() where db_schema_version < 1319;

ALTER TABLE vicidial_inbound_groups ADD dial_ingroup_cid VARCHAR(20) default '';

UPDATE system_settings SET db_schema_version='1320',db_schema_update_date=NOW() where db_schema_version < 1320;

ALTER TABLE vicidial_campaigns ADD safe_harbor_audio_field VARCHAR(30) default 'DISABLED';

UPDATE system_settings SET db_schema_version='1321',db_schema_update_date=NOW() where db_schema_version < 1321;