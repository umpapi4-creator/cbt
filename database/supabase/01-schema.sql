-- CBT Campus: PostgreSQL / Supabase, instalasi BARU.
-- Tempel SELURUH file di SQL Editor dan jalankan sebagai role postgres.
-- Jangan gunakan pada database yang sudah berisi tabel aplikasi ini.
-- CREATE TABLE sengaja tanpa IF NOT EXISTS: schema berbeda tidak disembunyikan.
-- Semua perubahan berada dalam satu transaksi; tidak menghapus data.
BEGIN;
SET LOCAL search_path TO public;

CREATE TABLE public.migrations (
    id serial PRIMARY KEY,
    migration varchar(255) NOT NULL,
    batch integer NOT NULL
);

CREATE TABLE public.users (
    id bigserial PRIMARY KEY,
    name varchar(255) NOT NULL,
    username varchar(255),
    email varchar(255) NOT NULL UNIQUE,
    profile_photo_path varchar(255),
    email_verified_at timestamp(0) without time zone,
    password varchar(255) NOT NULL,
    role varchar(255) NOT NULL DEFAULT 'student'
        CHECK (role IN ('admin', 'lecturer', 'student')),
    student_number varchar(255) UNIQUE,
    lecturer_number varchar(255) UNIQUE,
    created_by_lecturer_id bigint,
    remember_token varchar(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT users_username_unique UNIQUE (username),
    CONSTRAINT users_created_by_lecturer_id_foreign FOREIGN KEY (created_by_lecturer_id)
        REFERENCES public.users(id) ON DELETE SET NULL
);

CREATE TABLE public.password_reset_tokens (
    email varchar(255) PRIMARY KEY,
    token varchar(255) NOT NULL,
    created_at timestamp(0) without time zone
);

CREATE TABLE public.courses (
    id bigserial PRIMARY KEY,
    lecturer_id bigint NOT NULL,
    code varchar(255) NOT NULL,
    name varchar(255) NOT NULL,
    semester smallint NOT NULL DEFAULT 1,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT courses_lecturer_id_foreign FOREIGN KEY (lecturer_id)
        REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT courses_lecturer_id_code_unique UNIQUE (lecturer_id, code)
);

CREATE TABLE public.classrooms (
    id bigserial PRIMARY KEY,
    lecturer_id bigint NOT NULL,
    name varchar(255) NOT NULL,
    academic_year varchar(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT classrooms_lecturer_id_foreign FOREIGN KEY (lecturer_id)
        REFERENCES public.users(id) ON DELETE CASCADE
);

CREATE TABLE public.class_student (
    id bigserial PRIMARY KEY,
    classroom_id bigint NOT NULL,
    student_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT class_student_classroom_id_foreign FOREIGN KEY (classroom_id)
        REFERENCES public.classrooms(id) ON DELETE CASCADE,
    CONSTRAINT class_student_student_id_foreign FOREIGN KEY (student_id)
        REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT class_student_classroom_id_student_id_unique UNIQUE (classroom_id, student_id)
);

CREATE TABLE public.exams (
    id bigserial PRIMARY KEY,
    lecturer_id bigint NOT NULL,
    course_id bigint NOT NULL,
    classroom_id bigint NOT NULL,
    title varchar(255) NOT NULL,
    description text,
    starts_at timestamp(0) without time zone NOT NULL,
    ends_at timestamp(0) without time zone NOT NULL,
    duration_minutes integer NOT NULL DEFAULT 90,
    token varchar(12) NOT NULL,
    passing_score numeric(5,2) NOT NULL DEFAULT 60,
    shuffle_questions boolean NOT NULL DEFAULT true,
    shuffle_options boolean NOT NULL DEFAULT false,
    show_result boolean NOT NULL DEFAULT true,
    status varchar(255) NOT NULL DEFAULT 'draft'
        CHECK (status IN ('draft', 'published', 'completed')),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT exams_lecturer_id_foreign FOREIGN KEY (lecturer_id)
        REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT exams_course_id_foreign FOREIGN KEY (course_id)
        REFERENCES public.courses(id) ON DELETE CASCADE,
    CONSTRAINT exams_classroom_id_foreign FOREIGN KEY (classroom_id)
        REFERENCES public.classrooms(id) ON DELETE CASCADE
);

CREATE TABLE public.questions (
    id bigserial PRIMARY KEY,
    lecturer_id bigint NOT NULL,
    course_id bigint NOT NULL,
    exam_id bigint,
    text text NOT NULL,
    image varchar(255),
    explanation text,
    difficulty varchar(255) NOT NULL DEFAULT 'medium'
        CHECK (difficulty IN ('easy', 'medium', 'hard')),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT questions_lecturer_id_foreign FOREIGN KEY (lecturer_id)
        REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT questions_course_id_foreign FOREIGN KEY (course_id)
        REFERENCES public.courses(id) ON DELETE CASCADE,
    CONSTRAINT questions_exam_id_foreign FOREIGN KEY (exam_id)
        REFERENCES public.exams(id) ON DELETE CASCADE
);

CREATE TABLE public.question_options (
    id bigserial PRIMARY KEY,
    question_id bigint NOT NULL,
    label char(1) NOT NULL,
    text text NOT NULL,
    is_correct boolean NOT NULL DEFAULT false,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT question_options_question_id_foreign FOREIGN KEY (question_id)
        REFERENCES public.questions(id) ON DELETE CASCADE
);

CREATE TABLE public.exam_attempts (
    id bigserial PRIMARY KEY,
    exam_id bigint NOT NULL,
    student_id bigint NOT NULL,
    attempt_number integer NOT NULL DEFAULT 1,
    started_at timestamp(0) without time zone NOT NULL,
    deadline_at timestamp(0) without time zone NOT NULL,
    submitted_at timestamp(0) without time zone,
    status varchar(255) NOT NULL DEFAULT 'in_progress'
        CHECK (status IN ('in_progress', 'submitted')),
    question_order json,
    option_orders json,
    score numeric(5,2),
    correct_count integer NOT NULL DEFAULT 0,
    wrong_count integer NOT NULL DEFAULT 0,
    unanswered_count integer NOT NULL DEFAULT 0,
    tab_switch_count smallint NOT NULL DEFAULT 0,
    logout_count integer NOT NULL DEFAULT 0,
    terminated_for_tab_switch boolean NOT NULL DEFAULT false,
    terminated_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT exam_attempts_exam_id_foreign FOREIGN KEY (exam_id)
        REFERENCES public.exams(id) ON DELETE CASCADE,
    CONSTRAINT exam_attempts_student_id_foreign FOREIGN KEY (student_id)
        REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT exam_attempts_number_unique UNIQUE (exam_id, student_id, attempt_number)
);
CREATE INDEX exam_attempts_student_exam_index ON public.exam_attempts (exam_id, student_id);

CREATE TABLE public.exam_answers (
    id bigserial PRIMARY KEY,
    attempt_id bigint NOT NULL,
    question_id bigint NOT NULL,
    selected_option_id bigint,
    is_correct boolean,
    answered_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT exam_answers_attempt_id_foreign FOREIGN KEY (attempt_id)
        REFERENCES public.exam_attempts(id) ON DELETE CASCADE,
    CONSTRAINT exam_answers_question_id_foreign FOREIGN KEY (question_id)
        REFERENCES public.questions(id) ON DELETE CASCADE,
    CONSTRAINT exam_answers_selected_option_id_foreign FOREIGN KEY (selected_option_id)
        REFERENCES public.question_options(id) ON DELETE SET NULL,
    CONSTRAINT exam_answers_attempt_id_question_id_unique UNIQUE (attempt_id, question_id)
);

CREATE TABLE public.exam_tab_events (
    id bigserial PRIMARY KEY,
    attempt_id bigint NOT NULL,
    event_type varchar(40) NOT NULL DEFAULT 'visibility_hidden',
    occurred_at timestamp(0) without time zone NOT NULL,
    count_after smallint NOT NULL,
    notified_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT exam_tab_events_attempt_id_foreign FOREIGN KEY (attempt_id)
        REFERENCES public.exam_attempts(id) ON DELETE CASCADE
);
CREATE INDEX exam_tab_events_attempt_id_occurred_at_index ON public.exam_tab_events (attempt_id, occurred_at);
CREATE INDEX exam_tab_events_notified_at_index ON public.exam_tab_events (notified_at);

CREATE TABLE public.exam_retake_permissions (
    id bigserial PRIMARY KEY,
    exam_id bigint NOT NULL,
    student_id bigint NOT NULL,
    enabled_by_lecturer_id bigint,
    is_enabled boolean NOT NULL DEFAULT true,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT exam_retake_permissions_exam_id_foreign FOREIGN KEY (exam_id)
        REFERENCES public.exams(id) ON DELETE CASCADE,
    CONSTRAINT exam_retake_permissions_student_id_foreign FOREIGN KEY (student_id)
        REFERENCES public.users(id) ON DELETE CASCADE,
    CONSTRAINT exam_retake_permissions_enabled_by_lecturer_id_foreign FOREIGN KEY (enabled_by_lecturer_id)
        REFERENCES public.users(id) ON DELETE SET NULL,
    CONSTRAINT exam_retake_permissions_exam_id_student_id_unique UNIQUE (exam_id, student_id)
);

CREATE TABLE public.sessions (
    id varchar(255) PRIMARY KEY,
    user_id bigint,
    ip_address varchar(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);
CREATE INDEX sessions_user_id_index ON public.sessions (user_id);
CREATE INDEX sessions_last_activity_index ON public.sessions (last_activity);

-- Catat seluruh migration yang sudah diwakili schema ini.
-- Jangan jalankan ulang migration lama atau rollback batch baseline ini.
-- Akun admin dibuat terpisah melalui 02-admin.sql dengan password pilihanmu.
INSERT INTO public.migrations (migration, batch) VALUES
('2014_10_12_000000_create_users_table', 1),
('2014_10_12_100000_create_password_reset_tokens_table', 1),
('2026_01_01_000000_add_username_to_users_table', 1),
('2026_01_01_000001_create_courses_table', 1),
('2026_01_01_000002_create_classrooms_table', 1),
('2026_01_01_000003_create_questions_table', 1),
('2026_01_01_000004_create_exams_table', 1),
('2026_01_01_000005_create_exam_attempts_table', 1),
('2026_09_04_000006_add_exam_id_to_questions_table', 1),
('2026_09_04_000007_add_created_by_lecturer_to_users_table', 1),
('2026_09_04_000008_add_admin_role_to_users_table', 1),
('2026_09_04_000009_add_profile_photo_to_users_table', 1),
('2026_09_04_000010_add_exam_tab_monitoring', 1),
('2026_09_04_000011_add_exam_retake_support', 1),
('2026_10_20_000012_add_logout_count_to_exam_attempts', 1),
('2026_11_04_000013_create_sessions_table', 1);

-- Akses aplikasi melalui Laravel menggunakan koneksi postgres (pemilik tabel).
-- Login Laravel tidak menggunakan auth.users atau JWT Supabase.
-- Blokir akses tabel lewat anon/authenticated Data API; tidak ada policy publik.
DO $security$
DECLARE table_name text;
BEGIN
    FOREACH table_name IN ARRAY ARRAY[
        'migrations', 'users', 'password_reset_tokens', 'courses', 'classrooms',
        'class_student', 'exams', 'questions', 'question_options', 'exam_attempts',
        'exam_answers', 'exam_tab_events', 'exam_retake_permissions', 'sessions'
    ] LOOP
        EXECUTE format('ALTER TABLE public.%I ENABLE ROW LEVEL SECURITY', table_name);
        EXECUTE format('REVOKE ALL ON TABLE public.%I FROM PUBLIC, anon, authenticated', table_name);
    END LOOP;
END
$security$;

COMMIT;

-- Hasil: 14 tabel, termasuk migrations dan sessions; belum ada data akademik.
SELECT tablename, rowsecurity
FROM pg_tables
WHERE schemaname = 'public'
  AND tablename IN ('migrations', 'users', 'password_reset_tokens', 'courses',
    'classrooms', 'class_student', 'exams', 'questions', 'question_options',
    'exam_attempts', 'exam_answers', 'exam_tab_events', 'exam_retake_permissions', 'sessions')
ORDER BY tablename;
