CREATE TABLE mri_phantom (ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                          PhantomID VARCHAR(255) NOT NULL,
                          PRIMARY KEY (ID));

ALTER TABLE session ADD PhantomID VARCHAR(255) NOT NULL; 
ALTER TABLE session ADD CONSTRAINT FK_session_3 FOREIGN KEY (PhantomID)  REFERENCES mri_phantom(ID);
