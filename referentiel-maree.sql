-- Database generated with pgModeler (PostgreSQL Database Modeler).
-- pgModeler  version: 0.9.2-beta
-- PostgreSQL version: 9.6
-- Project Site: pgmodeler.io
-- Model Author: ---


-- Database creation must be done outside a multicommand file.
-- These commands were put in this file only as a convenience.
-- -- object: referentiel | type: DATABASE --
-- -- DROP DATABASE IF EXISTS referentiel;
-- CREATE DATABASE referentiel
-- 	ENCODING = 'UTF8'
-- 	LC_COLLATE = 'fr_FR.UTF-8'
-- 	LC_CTYPE = 'fr_FR.UTF-8'
-- 	TABLESPACE = pg_default
-- 	OWNER = quinton;
-- -- ddl-end --
-- 

-- object: maree | type: SCHEMA --
-- DROP SCHEMA IF EXISTS maree CASCADE;
CREATE SCHEMA maree;
-- ddl-end --

SET search_path TO pg_catalog,public,maree;
-- ddl-end --

-- object: maree.coef_coef_id_seq | type: SEQUENCE --
-- DROP SEQUENCE IF EXISTS maree.coef_coef_id_seq CASCADE;
CREATE SEQUENCE maree.coef_coef_id_seq
	INCREMENT BY 1
	MINVALUE 0
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE
	OWNED BY NONE;
-- ddl-end --


-- object: maree.coef | type: TABLE --
-- DROP TABLE IF EXISTS maree.coef CASCADE;
CREATE TABLE maree.coef (
	coef_id integer NOT NULL DEFAULT nextval('maree.coef_coef_id_seq'::regclass),
	coef_type_id integer NOT NULL,
	station_id integer,
	daydate date NOT NULL,
	hour time NOT NULL,
	coef double precision NOT NULL,
	hight double precision
);
-- ddl-end --
COMMENT ON TABLE maree.coef IS 'List of all coefficients';
-- ddl-end --
COMMENT ON COLUMN maree.coef.daydate IS 'Date of event';
-- ddl-end --
COMMENT ON COLUMN maree.coef.hour IS 'Hour of event';
-- ddl-end --
COMMENT ON COLUMN maree.coef.coef IS 'Coefficient';
-- ddl-end --
COMMENT ON COLUMN maree.coef.hight IS 'Hight of water';
-- ddl-end --


-- object: maree.coef_type | type: TABLE --
-- DROP TABLE IF EXISTS maree.coef_type CASCADE;
CREATE TABLE maree.coef_type (
	coef_type_id integer NOT NULL,
	coef_type_name character varying NOT NULL,
	CONSTRAINT coef_type_pk PRIMARY KEY (coef_type_id)

);
-- ddl-end --
COMMENT ON TABLE maree.coef_type IS 'List of types of coefficients';
-- ddl-end --


-- object: maree.station_station_id_seq | type: SEQUENCE --
-- DROP SEQUENCE IF EXISTS maree.station_station_id_seq CASCADE;
CREATE SEQUENCE maree.station_station_id_seq
	INCREMENT BY 1
	MINVALUE 0
	MAXVALUE 2147483647
	START WITH 1
	CACHE 1
	NO CYCLE
	OWNED BY NONE;
-- ddl-end --
ALTER SEQUENCE maree.station_station_id_seq OWNER TO quinton;
-- ddl-end --

-- object: maree.station | type: TABLE --
-- DROP TABLE IF EXISTS maree.station CASCADE;
CREATE TABLE maree.station (
	station_id integer NOT NULL DEFAULT nextval('maree.station_station_id_seq'::regclass),
	station_name character varying NOT NULL,
	pmheure95 smallint,
	pmheure45 smallint,
	bmheure95 smallint,
	bmheure45 smallint,
	CONSTRAINT station_pk PRIMARY KEY (station_id)

);
-- ddl-end --
COMMENT ON COLUMN maree.station.pmheure95 IS 'Décalage en minutes de la pleine mer, coefficient 95, par rapport à Royan';
-- ddl-end --
COMMENT ON COLUMN maree.station.pmheure45 IS 'Décalage en minutes de la pleine mer, coefficient 45, par rapport à Royan';
-- ddl-end --
COMMENT ON COLUMN maree.station.bmheure95 IS 'Décalage en minutes de la basse mer, coefficient 95, par rapport à Royan';
-- ddl-end --
COMMENT ON COLUMN maree.station.bmheure45 IS 'Décalage en minutes de la basse mer, coefficient 45, par rapport à Royan';
-- ddl-end --
ALTER TABLE maree.station OWNER TO quinton;
-- ddl-end --

INSERT INTO maree.station (station_name, pmheure95, pmheure45, bmheure95, bmheure45) VALUES (E'Bordeaux', E'150', E'120', E'260', E'215');
-- ddl-end --
INSERT INTO maree.station (station_name, pmheure95, pmheure45, bmheure95, bmheure45) VALUES (E'Pauillac', E'65', E'60', E'140', E'100');
-- ddl-end --
INSERT INTO maree.station (station_name, pmheure95, pmheure45, bmheure95, bmheure45) VALUES (E'Royan', E'0', E'0', E'0', E'0');
-- ddl-end --

-- object: maree.v_coef | type: VIEW --
-- DROP VIEW IF EXISTS maree.v_coef CASCADE;
CREATE VIEW maree.v_coef
AS 

SELECT coef.daydate,
    coef.hour,
    coef.coef,
    station.station_name,
    coef_type.coef_type_name
   FROM ((maree.coef
     JOIN maree.coef_type USING (coef_type_id))
     LEFT JOIN maree.station USING (station_id));
-- ddl-end --
ALTER VIEW maree.v_coef OWNER TO quinton;
-- ddl-end --

-- object: daydate_idx | type: INDEX --
-- DROP INDEX IF EXISTS maree.daydate_idx CASCADE;
CREATE INDEX daydate_idx ON maree.coef
	USING btree
	(
	  daydate
	)
	WITH (FILLFACTOR = 90);
-- ddl-end --

-- object: coef_coef_type_id_fk | type: CONSTRAINT --
-- ALTER TABLE maree.coef DROP CONSTRAINT IF EXISTS coef_coef_type_id_fk CASCADE;
ALTER TABLE maree.coef ADD CONSTRAINT coef_coef_type_id_fk FOREIGN KEY (coef_type_id)
REFERENCES maree.coef_type (coef_type_id) MATCH FULL
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: maree_station_fk | type: CONSTRAINT --
-- ALTER TABLE maree.coef DROP CONSTRAINT IF EXISTS maree_station_fk CASCADE;
ALTER TABLE maree.coef ADD CONSTRAINT maree_station_fk FOREIGN KEY (station_id)
REFERENCES maree.station (station_id) MATCH FULL
ON DELETE NO ACTION ON UPDATE NO ACTION;
-- ddl-end --

-- object: grant_a04c0cc2bc | type: PERMISSION --
GRANT CREATE,USAGE
   ON SCHEMA maree
   TO quinton;
-- ddl-end --



