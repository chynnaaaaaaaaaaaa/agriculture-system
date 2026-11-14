# Data Dictionary

Generated from the live Supabase/PostgreSQL schema (`database/schema.sql`).

## app_user

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| user_id | INT | NO | PK | identity (auto) |
| username | VARCHAR(50) | NO | UNIQUE |  |
| full_name | VARCHAR(100) | NO |  |  |
| password_hash | VARCHAR(255) | NO |  |  |
| role | VARCHAR(20) | NO |  |  |
| is_active | BOOLEAN | NO |  | true |
| created_at | TIMESTAMPTZ | NO |  | now() |
| updated_at | TIMESTAMPTZ | NO |  | now() |

CHECK constraints: `CHECK (((role)::text = ANY ((ARRAY['admin'::character varying, 'staff'::character varying])::text[])))`

## soil_type

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| soil_type_id | INT | NO | PK | identity (auto) |
| soil_type_name | VARCHAR(50) | NO | UNIQUE |  |

## irrigation_type

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| irrigation_type_id | INT | NO | PK | identity (auto) |
| irrigation_type_name | VARCHAR(50) | NO | UNIQUE |  |

## crop_category

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| crop_category_id | INT | NO | PK | identity (auto) |
| category_name | VARCHAR(50) | NO | UNIQUE |  |

## season

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| season_id | INT | NO | PK | identity (auto) |
| season_name | VARCHAR(30) | NO | UNIQUE |  |

## planting_status

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| status_id | INT | NO | PK | identity (auto) |
| status_name | VARCHAR(30) | NO | UNIQUE |  |

## quality_grade

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| grade_id | INT | NO | PK | identity (auto) |
| grade_name | VARCHAR(30) | NO | UNIQUE |  |

## harvest_remark

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| remark_id | INT | NO | PK | identity (auto) |
| remark_description | VARCHAR(100) | NO | UNIQUE |  |

## farmer

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| farmer_id | INT | NO | PK | identity (auto) |
| first_name | VARCHAR(50) | NO |  |  |
| last_name | VARCHAR(50) | NO |  |  |
| contact_number | VARCHAR(20) | NO | UNIQUE |  |
| address | VARCHAR(150) | NO |  |  |
| date_registered | DATE | NO |  | CURRENT_DATE |
| created_at | TIMESTAMPTZ | NO |  | now() |
| updated_at | TIMESTAMPTZ | NO |  | now() |
| created_by | INT | YES | FK → app_user.user_id |  |
| updated_by | INT | YES | FK → app_user.user_id |  |

CHECK constraints: `CHECK (((contact_number)::text ~ '^[0-9+() -]{7,20}$'::text))`

## farm

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| farm_id | INT | NO | PK | identity (auto) |
| farmer_id | INT | NO | FK → farmer.farmer_id, UNIQUE |  |
| farm_name | VARCHAR(100) | NO | UNIQUE |  |
| location | VARCHAR(150) | NO |  |  |
| area_hectares | NUMERIC(8,2) | NO |  |  |
| soil_type_id | INT | NO | FK → soil_type.soil_type_id |  |
| irrigation_type_id | INT | NO | FK → irrigation_type.irrigation_type_id |  |
| created_at | TIMESTAMPTZ | NO |  | now() |
| updated_at | TIMESTAMPTZ | NO |  | now() |
| created_by | INT | YES | FK → app_user.user_id |  |
| updated_by | INT | YES | FK → app_user.user_id |  |

CHECK constraints: `CHECK ((area_hectares > (0)::numeric))`

## crop

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| crop_id | INT | NO | PK | identity (auto) |
| crop_name | VARCHAR(80) | NO | UNIQUE |  |
| variety | VARCHAR(80) | NO | UNIQUE |  |
| crop_category_id | INT | NO | FK → crop_category.crop_category_id |  |
| growing_period_days | INT | NO |  |  |
| season_id | INT | NO | FK → season.season_id |  |
| created_at | TIMESTAMPTZ | NO |  | now() |
| updated_at | TIMESTAMPTZ | NO |  | now() |
| created_by | INT | YES | FK → app_user.user_id |  |
| updated_by | INT | YES | FK → app_user.user_id |  |

CHECK constraints: `CHECK ((growing_period_days > 0))`

## planting

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| planting_id | INT | NO | PK | identity (auto) |
| farm_id | INT | NO | FK → farm.farm_id |  |
| crop_id | INT | NO | FK → crop.crop_id |  |
| planting_date | DATE | NO |  |  |
| area_planted | NUMERIC(8,2) | NO |  |  |
| seed_quantity | NUMERIC(8,2) | NO |  |  |
| expected_harvest_date | DATE | YES |  |  |
| status_id | INT | NO | FK → planting_status.status_id |  |
| created_at | TIMESTAMPTZ | NO |  | now() |
| updated_at | TIMESTAMPTZ | NO |  | now() |
| created_by | INT | YES | FK → app_user.user_id |  |
| updated_by | INT | YES | FK → app_user.user_id |  |

CHECK constraints: `CHECK ((area_planted > (0)::numeric))`; `CHECK ((seed_quantity >= (0)::numeric))`; `CHECK (((expected_harvest_date IS NULL) OR (expected_harvest_date >= planting_date)))`

## harvest

| Column | Data type | Null? | Key | Default / notes |
|---|---|---|---|---|
| harvest_id | INT | NO | PK | identity (auto) |
| planting_id | INT | NO | FK → planting.planting_id |  |
| harvest_date | DATE | NO |  |  |
| quantity_kg | NUMERIC(10,2) | NO |  |  |
| selling_price_per_kg | NUMERIC(10,2) | NO |  |  |
| grade_id | INT | NO | FK → quality_grade.grade_id |  |
| remark_id | INT | NO | FK → harvest_remark.remark_id |  |
| created_at | TIMESTAMPTZ | NO |  | now() |
| updated_at | TIMESTAMPTZ | NO |  | now() |
| created_by | INT | YES | FK → app_user.user_id |  |
| updated_by | INT | YES | FK → app_user.user_id |  |

CHECK constraints: `CHECK ((quantity_kg >= (0)::numeric))`; `CHECK ((selling_price_per_kg >= (0)::numeric))`
