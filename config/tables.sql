-- Users
CREATE TABLE IF NOT EXISTS public.users
    (
        id integer NOT NULL GENERATED ALWAYS AS IDENTITY ( INCREMENT 1 START 1 MINVALUE 1 MAXVALUE 2147483647 CACHE 1 ),
        username character varying(255) COLLATE pg_catalog."default" NOT NULL,
        first_name character varying(255) COLLATE pg_catalog."default",
        last_name character varying(255) COLLATE pg_catalog."default",
        email character varying(255) COLLATE pg_catalog."default" NOT NULL,
        phone_number character varying(50) COLLATE pg_catalog."default",
        password character varying(255) COLLATE pg_catalog."default" NOT NULL,
        role character varying(50) COLLATE pg_catalog."default" NOT NULL,
        created_at timestamp with time zone DEFAULT now(),
        updated_at timestamp with time zone DEFAULT now(),
        CONSTRAINT users_pkey PRIMARY KEY (id),
        CONSTRAINT users_email_key UNIQUE (email),
        CONSTRAINT users_username_key UNIQUE (username),
        CONSTRAINT users_role_check CHECK (role::text = ANY (ARRAY['admin'::character varying::text, 'technician'::character varying::text, 'user'::character varying::text]))
    )

-- Tickets
    CREATE TABLE IF NOT EXISTS public.tickets
    (
        id integer NOT NULL DEFAULT nextval('tickets_id_seq'::regclass),
        ticket_id character varying(255) COLLATE pg_catalog."default" NOT NULL,
        full_name character varying(255) COLLATE pg_catalog."default" NOT NULL,
        email character varying(255) COLLATE pg_catalog."default" NOT NULL,
        department character varying(255) COLLATE pg_catalog."default" NOT NULL,
        subject character varying(255) COLLATE pg_catalog."default" NOT NULL,
        description text COLLATE pg_catalog."default" NOT NULL,
        status character varying(50) COLLATE pg_catalog."default" NOT NULL DEFAULT 'Open'::character varying,
        assigned_to character varying(255) COLLATE pg_catalog."default" DEFAULT NULL::character varying,
        created_at timestamp with time zone DEFAULT now(),
        updated_at timestamp with time zone DEFAULT now(),
        CONSTRAINT tickets_pkey PRIMARY KEY (id),
        CONSTRAINT tickets_ticket_id_key UNIQUE (ticket_id)
    )
