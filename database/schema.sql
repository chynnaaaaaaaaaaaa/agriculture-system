-- =====================================================================
-- Agriculture Information System  -  Supabase PostgreSQL schema (3NF)
-- Run this whole file once in:  Supabase Dashboard > SQL Editor > New query
-- Safe to re-run: it drops and recreates every table.
-- =====================================================================

DROP TABLE IF EXISTS harvest, planting, farm, crop, farmer, app_user CASCADE;
DROP TABLE IF EXISTS harvest_remark, quality_grade, planting_status, season,
                     crop_category, irrigation_type, soil_type CASCADE;
DROP FUNCTION IF EXISTS set_updated_at() CASCADE;
DROP FUNCTION IF EXISTS check_harvest_date() CASCADE;

-- ---------------------------------------------------------------------
-- Audit trigger: keeps updated_at current on every UPDATE
-- ---------------------------------------------------------------------
CREATE FUNCTION set_updated_at() RETURNS trigger AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ---------------------------------------------------------------------
-- USERS (authentication + roles)
-- ---------------------------------------------------------------------
CREATE TABLE app_user (
    user_id       INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    full_name     VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,              -- bcrypt hash, never plain text
    role          VARCHAR(20)  NOT NULL CHECK (role IN ('admin', 'staff')),
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------
-- LOOKUP / REFERENCE TABLES  (dropdown values live here)
-- ---------------------------------------------------------------------
CREATE TABLE soil_type (
    soil_type_id   INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    soil_type_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE irrigation_type (
    irrigation_type_id   INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    irrigation_type_name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE crop_category (
    crop_category_id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    category_name    VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE season (
    season_id   INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    season_name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE planting_status (
    status_id   INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    status_name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE quality_grade (
    grade_id   INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    grade_name VARCHAR(30) NOT NULL UNIQUE
);

CREATE TABLE harvest_remark (
    remark_id          INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    remark_description VARCHAR(100) NOT NULL UNIQUE
);

-- Lookup data (order matters: harvest_remark ids must be 1..5 as in the spec)
INSERT INTO soil_type (soil_type_name) VALUES
    ('Loam'), ('Clay'), ('Clay Loam'), ('Sandy Loam'), ('Sandy'), ('Silty');

INSERT INTO irrigation_type (irrigation_type_name) VALUES
    ('Rainfed'), ('Irrigated'), ('Drip Irrigation'), ('Sprinkler');

INSERT INTO crop_category (category_name) VALUES
    ('Cereal'), ('Vegetable'), ('Fruit'), ('Legume'), ('Root Crop');

INSERT INTO season (season_name) VALUES
    ('Wet Season'), ('Dry Season'), ('Year Round');

INSERT INTO planting_status (status_name) VALUES
    ('Planted'), ('Growing'), ('Harvested'), ('Failed');

INSERT INTO quality_grade (grade_name) VALUES
    ('Grade A'), ('Grade B'), ('Grade C'), ('Grade D');

INSERT INTO harvest_remark (remark_description) VALUES
    ('Good Harvest'),        -- 1
    ('Acceptable Quality'),  -- 2
    ('Low Yield'),           -- 3
    ('Poor Quality'),        -- 4
    ('Damaged Harvest');     -- 5

-- ---------------------------------------------------------------------
-- FARMER
-- ---------------------------------------------------------------------
CREATE TABLE farmer (
    farmer_id       INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    first_name      VARCHAR(50)  NOT NULL,
    last_name       VARCHAR(50)  NOT NULL,
    contact_number  VARCHAR(20)  NOT NULL UNIQUE
                    CHECK (contact_number ~ '^[0-9+() -]{7,20}$'),
    address         VARCHAR(150) NOT NULL,
    date_registered DATE         NOT NULL DEFAULT CURRENT_DATE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    created_by      INT REFERENCES app_user(user_id) ON DELETE SET NULL,
    updated_by      INT REFERENCES app_user(user_id) ON DELETE SET NULL
);

-- ---------------------------------------------------------------------
-- FARM   (FARMER 1:M FARM)
-- ---------------------------------------------------------------------
CREATE TABLE farm (
    farm_id            INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    farmer_id          INT           NOT NULL REFERENCES farmer(farmer_id)
                                     ON DELETE RESTRICT,
    farm_name          VARCHAR(100)  NOT NULL,
    location           VARCHAR(150)  NOT NULL,
    area_hectares      NUMERIC(8,2)  NOT NULL CHECK (area_hectares > 0),
    soil_type_id       INT           NOT NULL REFERENCES soil_type(soil_type_id),
    irrigation_type_id INT           NOT NULL REFERENCES irrigation_type(irrigation_type_id),
    created_at         TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    created_by         INT REFERENCES app_user(user_id) ON DELETE SET NULL,
    updated_by         INT REFERENCES app_user(user_id) ON DELETE SET NULL,
    CONSTRAINT uq_farm_per_farmer UNIQUE (farmer_id, farm_name)
);

-- ---------------------------------------------------------------------
-- CROP   (master data)
-- ---------------------------------------------------------------------
CREATE TABLE crop (
    crop_id             INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    crop_name           VARCHAR(80) NOT NULL,
    variety             VARCHAR(80) NOT NULL,
    crop_category_id    INT         NOT NULL REFERENCES crop_category(crop_category_id),
    growing_period_days INT         NOT NULL CHECK (growing_period_days > 0),
    season_id           INT         NOT NULL REFERENCES season(season_id),
    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    created_by          INT REFERENCES app_user(user_id) ON DELETE SET NULL,
    updated_by          INT REFERENCES app_user(user_id) ON DELETE SET NULL,
    CONSTRAINT uq_crop_variety UNIQUE (crop_name, variety)
);

-- ---------------------------------------------------------------------
-- PLANTING   (FARM 1:M PLANTING, CROP 1:M PLANTING)
-- ---------------------------------------------------------------------
CREATE TABLE planting (
    planting_id           INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    farm_id               INT          NOT NULL REFERENCES farm(farm_id) ON DELETE RESTRICT,
    crop_id               INT          NOT NULL REFERENCES crop(crop_id) ON DELETE RESTRICT,
    planting_date         DATE         NOT NULL,
    area_planted          NUMERIC(8,2) NOT NULL CHECK (area_planted > 0),
    seed_quantity         NUMERIC(8,2) NOT NULL CHECK (seed_quantity >= 0),
    expected_harvest_date DATE,
    status_id             INT          NOT NULL REFERENCES planting_status(status_id),
    created_at            TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at            TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    created_by            INT REFERENCES app_user(user_id) ON DELETE SET NULL,
    updated_by            INT REFERENCES app_user(user_id) ON DELETE SET NULL,
    CONSTRAINT chk_expected_harvest
        CHECK (expected_harvest_date IS NULL OR expected_harvest_date >= planting_date)
);

-- ---------------------------------------------------------------------
-- HARVEST   (PLANTING 1:M HARVEST)
-- ---------------------------------------------------------------------
CREATE TABLE harvest (
    harvest_id           INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    planting_id          INT           NOT NULL REFERENCES planting(planting_id) ON DELETE RESTRICT,
    harvest_date         DATE          NOT NULL,
    quantity_kg          NUMERIC(10,2) NOT NULL CHECK (quantity_kg >= 0),
    selling_price_per_kg NUMERIC(10,2) NOT NULL CHECK (selling_price_per_kg >= 0),
    grade_id             INT           NOT NULL REFERENCES quality_grade(grade_id),
    remark_id            INT           NOT NULL REFERENCES harvest_remark(remark_id),
    created_at           TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    updated_at           TIMESTAMPTZ   NOT NULL DEFAULT NOW(),
    created_by           INT REFERENCES app_user(user_id) ON DELETE SET NULL,
    updated_by           INT REFERENCES app_user(user_id) ON DELETE SET NULL
);

-- Cross-table rule: a harvest can never happen before its planting date
CREATE FUNCTION check_harvest_date() RETURNS trigger AS $$
DECLARE p_date DATE;
BEGIN
    SELECT planting_date INTO p_date FROM planting WHERE planting_id = NEW.planting_id;
    IF NEW.harvest_date < p_date THEN
        RAISE EXCEPTION 'Harvest date (%) cannot be earlier than the planting date (%)',
            NEW.harvest_date, p_date USING ERRCODE = '23514';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_harvest_date BEFORE INSERT OR UPDATE ON harvest
    FOR EACH ROW EXECUTE FUNCTION check_harvest_date();

-- updated_at triggers
CREATE TRIGGER trg_upd_app_user BEFORE UPDATE ON app_user FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_upd_farmer   BEFORE UPDATE ON farmer   FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_upd_farm     BEFORE UPDATE ON farm     FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_upd_crop     BEFORE UPDATE ON crop     FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_upd_planting BEFORE UPDATE ON planting FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER trg_upd_harvest  BEFORE UPDATE ON harvest  FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Helpful indexes for joins / filters
CREATE INDEX idx_farm_farmer       ON farm(farmer_id);
CREATE INDEX idx_planting_farm     ON planting(farm_id);
CREATE INDEX idx_planting_crop     ON planting(crop_id);
CREATE INDEX idx_planting_status   ON planting(status_id);
CREATE INDEX idx_harvest_planting  ON harvest(planting_id);
CREATE INDEX idx_harvest_date      ON harvest(harvest_date);

-- ---------------------------------------------------------------------
-- SECURITY: Supabase exposes every public table through its REST API.
-- Turning on Row Level Security with NO policies blocks that API completely.
-- Our PHP app connects directly as the database owner, which is not affected.
-- ---------------------------------------------------------------------
ALTER TABLE app_user         ENABLE ROW LEVEL SECURITY;
ALTER TABLE farmer           ENABLE ROW LEVEL SECURITY;
ALTER TABLE farm             ENABLE ROW LEVEL SECURITY;
ALTER TABLE crop             ENABLE ROW LEVEL SECURITY;
ALTER TABLE planting         ENABLE ROW LEVEL SECURITY;
ALTER TABLE harvest          ENABLE ROW LEVEL SECURITY;
ALTER TABLE soil_type        ENABLE ROW LEVEL SECURITY;
ALTER TABLE irrigation_type  ENABLE ROW LEVEL SECURITY;
ALTER TABLE crop_category    ENABLE ROW LEVEL SECURITY;
ALTER TABLE season           ENABLE ROW LEVEL SECURITY;
ALTER TABLE planting_status  ENABLE ROW LEVEL SECURITY;
ALTER TABLE quality_grade    ENABLE ROW LEVEL SECURITY;
ALTER TABLE harvest_remark   ENABLE ROW LEVEL SECURITY;

-- ---------------------------------------------------------------------
-- Default login accounts (CHANGE THESE PASSWORDS after first login)
--   admin / Admin@123      staff / Staff@123
-- ---------------------------------------------------------------------
INSERT INTO app_user (username, full_name, password_hash, role) VALUES
 ('admin', 'System Administrator', '$2y$10$eSdO5zpPDVP/EEiGNPoJPO1CPhRlOZVE4EW5tP73ikoSHQVVsQGT6', 'admin'),
 ('staff', 'Staff User',           '$2y$10$/4qWwd.IAq8Zg5e8dMkPGe5HCEUZoszU56XLjahhkJJDe99xhzK3G', 'staff');
