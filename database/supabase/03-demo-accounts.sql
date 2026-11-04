-- Jalankan sebagai postgres SETELAH 01-schema.sql.
-- Akun demo untuk pengujian; password mengikuti tampilan login aplikasi.
-- Akun yang bentrok username/email/NIM/NIDN dilewati, bukan ditimpa.
-- Admin existing dari 02-admin.sql tetap dipertahankan.
BEGIN;
CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA extensions;
SET LOCAL search_path TO public, extensions;

INSERT INTO public.users
    (name, username, email, password, role, student_number, lecturer_number, created_at, updated_at)
VALUES
    ('Admin Demo', 'tesadmin', 'demo.admin@cbt.test',
     crypt('tesadmin', gen_salt('bf', 10)), 'admin', NULL, NULL,
     timezone('Asia/Jakarta', now()), timezone('Asia/Jakarta', now())),
    ('Dosen Demo', 'tesdosen', 'demo.dosen@cbt.test',
     crypt('tessdosen', gen_salt('bf', 10)), 'lecturer', NULL, 'DEMO-DSN001',
     timezone('Asia/Jakarta', now()), timezone('Asia/Jakarta', now())),
    ('Mahasiswa Demo', 'tes', 'demo.student@cbt.test',
     crypt('tes', gen_salt('bf', 10)), 'student', 'DEMO-MHS001', NULL,
     timezone('Asia/Jakarta', now()), timezone('Asia/Jakarta', now()))
ON CONFLICT DO NOTHING;

COMMIT;

-- Jika username sudah ada sebelumnya, password/role akun itu TIDAK diubah.
SELECT id, name, username, email, role
FROM public.users
WHERE username IN ('tesadmin', 'tesdosen', 'tes')
ORDER BY username;
