-- Table: lychee_log

-- DROP TABLE lychee_log;

CREATE TABLE IF NOT EXISTS _PREFIX_lychee_log
(
  id serial NOT NULL,
  "time" integer NOT NULL,
  type character varying(11) NOT NULL,
  function character varying(100) NOT NULL,
  line integer NOT NULL,
  text text,
  CONSTRAINT pk__PREFIX_lychee_log_id PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

