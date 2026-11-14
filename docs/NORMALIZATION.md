# Database Normalization: UNF → 1NF → 2NF → 3NF

## 0. Unnormalized Form (UNF)

One big "Harvest Report" sheet as a farmer's record might look on paper. `{ }` marks a **repeating group**.

```
AGRI_RECORD(
  Farmer_ID, Farmer_Name, Contact_Numbers, Address, Date_Registered,
  { Farm_ID, Farm_Name, Location, Area_Hectares, Soil_Type, Irrigation_Type,
    { Planting_ID, Crop_Name, Variety, Crop_Category, Growing_Period_Days, Recommended_Season,
      Planting_Date, Area_Planted, Seed_Quantity, Expected_Harvest_Date, Status,
      { Harvest_ID, Harvest_Date, Quantity_Kg, Selling_Price_Per_Kg, Quality_Grade, Remarks } } } )
```
Problems: repeating groups (many farms per farmer, many plantings per farm, many harvests per planting), a
non-atomic `Farmer_Name` and possibly several numbers in `Contact_Numbers`, and lots of duplicated data.

## 1. First Normal Form (1NF)
Rules: every field atomic, no repeating groups, every row identified by a key.

* `Farmer_Name` → `first_name`, `last_name`. One contact number per farmer.
* Each repeating group becomes its own relation, carrying the parent's key:

```
FARMER   (Farmer_ID PK, First_Name, Last_Name, Contact_Number, Address, Date_Registered)
FARM     (Farm_ID PK, Farmer_ID FK, Farm_Name, Location, Area_Hectares, Soil_Type, Irrigation_Type)
PLANTING (Planting_ID PK, Farm_ID FK, Crop_Name, Variety, Crop_Category, Growing_Period_Days,
          Recommended_Season, Planting_Date, Area_Planted, Seed_Quantity, Expected_Harvest_Date, Status)
HARVEST  (Harvest_ID PK, Planting_ID FK, Harvest_Date, Quantity_Kg, Selling_Price_Per_Kg,
          Quality_Grade, Remarks)
```

## 2. Second Normal Form (2NF)
Rules: 1NF + no **partial dependency** (a non-key column depending on only part of a composite key).
Anything that describes the *crop* rather than the *planting event* depends only on the crop, so it is
removed from `PLANTING` (if the key were `Planting_ID + Crop_Name`, `Variety`, `Crop_Category`,
`Growing_Period_Days`, `Recommended_Season` would depend on `Crop_Name` alone).

```
CROP     (Crop_ID PK, Crop_Name, Variety, Crop_Category, Growing_Period_Days, Recommended_Season)
PLANTING (Planting_ID PK, Farm_ID FK, Crop_ID FK, Planting_Date, Area_Planted, Seed_Quantity,
          Expected_Harvest_Date, Status)
```
All other tables already use a single-column surrogate primary key (identity), so they are in 2NF.

## 3. Third Normal Form (3NF)
Rules: 2NF + no **transitive dependency** (non-key column depending on another non-key column).
Repeated descriptive text such as `Soil_Type`, `Irrigation_Type`, `Crop_Category`, `Recommended_Season`,
`Status`, `Quality_Grade` and `Remarks` is a value from a fixed list. Storing the words in each row lets them
be misspelt and repeated; each word becomes a lookup table and the main table stores only its ID.

| Lookup table | Replaces column | Used by |
|---|---|---|
| `soil_type` | Soil_Type | `farm.soil_type_id` |
| `irrigation_type` | Irrigation_Type | `farm.irrigation_type_id` |
| `crop_category` | Crop_Category | `crop.crop_category_id` |
| `season` | Recommended_Season | `crop.season_id` |
| `planting_status` | Status | `planting.status_id` |
| `quality_grade` | Quality_Grade | `harvest.grade_id` |
| `harvest_remark` | Remarks | `harvest.remark_id` (1 Good Harvest … 5 Damaged Harvest) |

### Final 3NF design
```
APP_USER (user_id PK, username UQ, full_name, password_hash, role, is_active, created_at, updated_at)
FARMER   (farmer_id PK, first_name, last_name, contact_number UQ, address, date_registered, audit cols)
FARM     (farm_id PK, farmer_id FK, farm_name, location, area_hectares, soil_type_id FK,
          irrigation_type_id FK, audit cols)                       UQ(farmer_id, farm_name)
CROP     (crop_id PK, crop_name, variety, crop_category_id FK, growing_period_days, season_id FK, audit cols)
                                                                   UQ(crop_name, variety)
PLANTING (planting_id PK, farm_id FK, crop_id FK, planting_date, area_planted, seed_quantity,
          expected_harvest_date, status_id FK, audit cols)
HARVEST  (harvest_id PK, planting_id FK, harvest_date, quantity_kg, selling_price_per_kg,
          grade_id FK, remark_id FK, audit cols)
+ 7 lookup tables (id PK, name UQ)
audit cols = created_at, updated_at, created_by FK→app_user, updated_by FK→app_user
```
Every non-key attribute now depends on **"the key, the whole key, and nothing but the key."**

### Relationships
* FARMER 1 : M FARM  · FARM 1 : M PLANTING · CROP 1 : M PLANTING · PLANTING 1 : M HARVEST
* Each lookup table 1 : M its parent table(s).
* All foreign keys use `ON DELETE RESTRICT` (default for lookups), so a record in use cannot be deleted by accident.

See `ERD.png` for the diagram and `DATA_DICTIONARY.md` for every column and constraint.
