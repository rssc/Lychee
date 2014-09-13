-- Table: lychee_albums

-- DROP TABLE lychee_albums;

CREATE TABLE IF NOT EXISTS _PREFIX_lychee_albums
(
  id serial NOT NULL,
  title character varying(50) NOT NULL,
  description character varying(1000) DEFAULT ''::character varying,
  sysstamp integer NOT NULL,
  public smallint NOT NULL DEFAULT 0,
  visible smallint NOT NULL DEFAULT 1,
  downloadable smallint NOT NULL DEFAULT 0,
  password character varying(100) DEFAULT ''::character varying,
  CONSTRAINT pk__PREFIX_lychee_albums_id PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

