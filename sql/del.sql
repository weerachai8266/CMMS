DELETE FROM mt_approval_log;
DELETE FROM mt_repair;

ALTER TABLE mt_repair AUTO_INCREMENT = 1;
ALTER TABLE mt_approval_log AUTO_INCREMENT = 1;

DELETE FROM mt_machine_history;
ALTER TABLE mt_machine_history AUTO_INCREMENT = 1;