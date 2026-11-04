-- Jalankan SETELAH 01-schema.sql, sebagai postgres di SQL Editor Supabase.
-- Ganti nilai admin_email, admin_username dan admin_password di bawah.
-- Jangan commit salinan berisi password asli ke GitHub.
-- Script tidak menimpa akun existing; username/email yang sama menghasilkan error.
BEGIN;
CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA extensions;
SET LOCAL search_path TO public, extensions;

DO $admin$
DECLARE
    admin_email text := 'admin@cbt.test';
    admin_username text := 'admin';
    admin_password text := 'GANTI_PASSWORD_ADMIN_DI_SINI';
BEGIN
    IF admin_password = 'GANTI_PASSWORD_ADMIN_DI_SINI'
        OR length(admin_password) < 12 OR octet_length(admin_password) > 72 THEN
        RAISE EXCEPTION 'Ganti admin_password dengan password 12 karakter atau lebih (maksimal 72 byte).';
    END IF;

    INSERT INTO public.users (name, username, email, password, role, created_at, updated_at)
    VALUES ('Admin CBT', lower(admin_username), lower(admin_email),
        crypt(admin_password, gen_salt('bf', 10)), 'admin',
        timezone('Asia/Jakarta', now()), timezone('Asia/Jakarta', now()));
END
$admin$;
COMMIT;

SELECT id, name, username, email, role FROM public.users WHERE role = 'admin';
