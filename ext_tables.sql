CREATE TABLE tx_amt_pinecone_pineconeindex
(
    uid               int(11)              NOT NULL AUTO_INCREMENT,
    record_uid        int(11)              NOT NULL,
    tablename         varchar(255)         NOT NULL,
    is_indexed        tinyint(1) DEFAULT 0 NOT NULL,
    indexed_timestamp int(11)    DEFAULT 0 NOT NULL,
    deleted           tinyint(1) DEFAULT 0 NOT NULL,
    PRIMARY KEY (uid)
);

CREATE TABLE tx_amt_pinecone_configindex
(
    uid          int(11)      NOT NULL AUTO_INCREMENT,
    tablename    varchar(255) NOT NULL,
    pid          int(11)      NOT NULL DEFAULT 0,
    PRIMARY KEY (uid)
);
