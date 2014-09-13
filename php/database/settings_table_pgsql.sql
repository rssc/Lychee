-- Table: lychee_settings

-- DROP TABLE lychee_settings;

CREATE TABLE IF NOT EXISTS _PREFIX_lychee_settings
(
  key character varying(50) NOT NULL,
  value character varying(200),
  CONSTRAINT pk__PREFIX_lychee_settings_key PRIMARY KEY (key)
)
WITH (
  OIDS=FALSE
);

