--
-- PostgreSQL database dump
--

\restrict Of5IQAtJlr4zjODWCUo3yjSsPru3c6xzCNF6lN8qv6bGdawK2IWQ4rlDegzFe4F

-- Dumped from database version 14.19 (Ubuntu 14.19-0ubuntu0.22.04.1)
-- Dumped by pg_dump version 14.19 (Ubuntu 14.19-0ubuntu0.22.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: vector; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS vector WITH SCHEMA public;


--
-- Name: EXTENSION vector; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION vector IS 'vector data type and ivfflat and hnsw access methods';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: api_key_usage; Type: TABLE; Schema: public; Owner: jackson
--

CREATE TABLE public.api_key_usage (
    api_key text NOT NULL,
    time_window timestamp without time zone NOT NULL,
    count integer DEFAULT 0 NOT NULL
);


ALTER TABLE public.api_key_usage OWNER TO jackson;

--
-- Name: api_keys; Type: TABLE; Schema: public; Owner: jackson
--

CREATE TABLE public.api_keys (
    id integer NOT NULL,
    api_key text NOT NULL,
    description text,
    active boolean DEFAULT true,
    created_at timestamp without time zone DEFAULT now(),
    admin boolean DEFAULT false,
    rate_limit integer DEFAULT 60
);


ALTER TABLE public.api_keys OWNER TO jackson;

--
-- Name: api_keys_id_seq; Type: SEQUENCE; Schema: public; Owner: jackson
--

CREATE SEQUENCE public.api_keys_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.api_keys_id_seq OWNER TO jackson;

--
-- Name: api_keys_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: jackson
--

ALTER SEQUENCE public.api_keys_id_seq OWNED BY public.api_keys.id;


--
-- Name: wordembeddings; Type: TABLE; Schema: public; Owner: jackson
--

CREATE TABLE public.wordembeddings (
    word character varying(40) NOT NULL,
    embedding public.vector(300)
);


ALTER TABLE public.wordembeddings OWNER TO jackson;

--
-- Name: api_keys id; Type: DEFAULT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.api_keys ALTER COLUMN id SET DEFAULT nextval('public.api_keys_id_seq'::regclass);


--
-- Name: api_key_usage api_key_usage_pkey; Type: CONSTRAINT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.api_key_usage
    ADD CONSTRAINT api_key_usage_pkey PRIMARY KEY (api_key, time_window);


--
-- Name: api_keys api_keys_api_key_key; Type: CONSTRAINT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.api_keys
    ADD CONSTRAINT api_keys_api_key_key UNIQUE (api_key);


--
-- Name: api_keys api_keys_pkey; Type: CONSTRAINT; Schema: public; Owner: jackson
--

ALTER TABLE ONLY public.api_keys
    ADD CONSTRAINT api_keys_pkey PRIMARY KEY (id);


--
-- PostgreSQL database dump complete
--

\unrestrict Of5IQAtJlr4zjODWCUo3yjSsPru3c6xzCNF6lN8qv6bGdawK2IWQ4rlDegzFe4F

