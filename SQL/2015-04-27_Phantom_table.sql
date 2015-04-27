CREATE TABLE mri_phantom (ID INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                          PhantomID VARCHAR(255) NOT NULL,
                          PRIMARY KEY (ID),
                          CONSTRAINT FK_session FOREIGN KEY (ID) REFERENCES session (ID)
                          ON DELETE NO ACTION
                          ON UPDATE NO ACTION);
