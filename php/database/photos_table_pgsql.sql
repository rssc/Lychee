-- DROP TABLE lychee_photos;

CREATE TABLE IF NOT EXISTS _PREFIX_lychee_photos
(
  id bigserial NOT NULL,
  title character varying(50) NOT NULL,
  description character varying(1000) DEFAULT ''::character varying,
  url character varying(100) NOT NULL,
  tags character varying(1000) NOT NULL DEFAULT ''::character varying,
  public smallint NOT NULL,
  type character varying(10) NOT NULL,
  width integer NOT NULL,
  height integer NOT NULL,
  size character varying(20) NOT NULL,
  iso character varying(15) NOT NULL,
  aperture character varying(20) NOT NULL,
  make character varying(50) NOT NULL,
  model character varying(50) NOT NULL,
  shutter character varying(30) NOT NULL,
  focal character varying(20) NOT NULL,
  takestamp integer,
  star smallint NOT NULL,
  thumburl character varying(50) NOT NULL,
  album character varying(30) NOT NULL DEFAULT '0'::character varying,
  checksum character varying(100) DEFAULT NULL::character varying,
  CONSTRAINT pk__PREFIX_lychee_photos_id PRIMARY KEY (id)
)
WITH (
  OIDS=FALSE
);

