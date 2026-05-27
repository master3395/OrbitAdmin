-- OrbitAdmin SQLite schema (0.1.0)

CREATE TABLE IF NOT EXISTS "users" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "username" TEXT NOT NULL UNIQUE,
  "email" TEXT NOT NULL UNIQUE,
  "name" TEXT,
  "password_hash" TEXT NOT NULL,
  "role" TEXT NOT NULL DEFAULT 'Viewer',
  "active" INTEGER NOT NULL DEFAULT 1,
  "totp_secret" TEXT,
  "last_login_at" TEXT,
  "last_login_ip" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);

CREATE TABLE IF NOT EXISTS "roles" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "name" TEXT NOT NULL UNIQUE,
  "description" TEXT,
  "permissions" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);

CREATE TABLE IF NOT EXISTS "permissions" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "key" TEXT NOT NULL UNIQUE,
  "label" TEXT NOT NULL,
  "created_at" TEXT,
  "updated_at" TEXT
);

CREATE TABLE IF NOT EXISTS "activity" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "user_id" INTEGER,
  "actor" TEXT,
  "action" TEXT NOT NULL,
  "target" TEXT,
  "ip" TEXT,
  "ua" TEXT,
  "meta" TEXT,
  "prev_hash" TEXT,
  "hash" TEXT,
  "created_at" TEXT
);

CREATE TABLE IF NOT EXISTS "tokens" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "user_id" INTEGER NOT NULL,
  "name" TEXT NOT NULL,
  "prefix" TEXT NOT NULL,
  "last4" TEXT NOT NULL,
  "hash" TEXT NOT NULL,
  "scopes" TEXT,
  "expires_at" TEXT,
  "last_used_at" TEXT,
  "revoked_at" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);

CREATE TABLE IF NOT EXISTS "email_templates" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "slug" TEXT NOT NULL UNIQUE,
  "subject" TEXT NOT NULL,
  "body" TEXT NOT NULL,
  "variables" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);

CREATE TABLE IF NOT EXISTS "settings" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "key" TEXT NOT NULL UNIQUE,
  "value" TEXT,
  "created_at" TEXT,
  "updated_at" TEXT
);

CREATE TABLE IF NOT EXISTS "_migrations" (
  "id" INTEGER PRIMARY KEY AUTOINCREMENT,
  "version" TEXT NOT NULL UNIQUE,
  "applied_at" TEXT
);
