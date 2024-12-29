CREATE user openeyes;

SET
    PASSWORD FOR openeyes = PASSWORD ('openeyes');

CREATE database openeyes;
GRANT ALL PRIVILEGES ON *.* TO 'openeyes'@'%';
FLUSH PRIVILEGES;