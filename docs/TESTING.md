# System Testing

Run each test on your own Supabase-connected copy, write **Pass/Fail** and the date in the last column,
and take a screenshot for the ones marked 📷. (Before packaging, all of these behaviours were verified by an
automated run of 91 checks against a PostgreSQL copy of the same schema — you still need to run them on
your own Supabase project for your submission.)

| # | Module | Test | Expected result | Result |
|---|---|---|---|---|
| 1 | Auth | Open any page while logged out | Redirected to login page | |
| 2 | Auth | Login with wrong password | "Incorrect username or password" 📷 | |
| 3 | Auth | Login `admin` / `Admin@123` | Dashboard opens | |
| 4 | Auth | Logout, then press browser Back | Protected page redirects to login | |
| 5 | Roles | Login as `staff` | No Delete buttons; no Add/Edit on Crops | |
| 6 | Roles | Staff opens `/crops/form.php` by URL | "Administrators only" message | |
| 7 | Dashboard | Cards show 15 / 15 / 10 / 50 / 20 | Values match database counts 📷 | |
| 8 | Dashboard | Add a farmer, return to dashboard | Farmer count increases by 1 | |
| 9 | Farmers | Create with valid data | Success message, appears in list 📷 | |
| 10 | Farmers | Create with empty fields | Required-field messages 📷 | |
| 11 | Farmers | Contact number `abc` | "Use 7-20 digits…" | |
| 12 | Farmers | Duplicate contact number | "already registered" | |
| 13 | Farmers | Edit then save | Success message, changes shown | |
| 14 | Farmers | Delete farmer with farms | Confirmation dialog 📷, then "cannot be deleted" 📷 | |
| 15 | Farmers | Delete farmer with no farms | Confirmation, then success message | |
| 16 | Farmers | Search "santos"; filter by address | Only matching rows | |
| 17 | Farmers | Click column headings | Sorts ascending/descending | |
| 18 | Farms | Create with negative area | Area error message | |
| 19 | Farms | Dropdowns (farmer, soil, irrigation) | Show names; DB stores IDs | |
| 20 | Farms | Filter by irrigation type | Only matching farms | |
| 21 | Crops | (admin) create duplicate crop+variety | "already exists" | |
| 22 | Crops | Growing period `0` or `1000` | Error: 1 to 730 | |
| 23 | Crops | Delete crop used in plantings | Blocked with message | |
| 24 | Plantings | Invalid date `2026-02-31` | "Enter a valid date" | |
| 25 | Plantings | Expected date before planting date | Error message | |
| 26 | Plantings | Area planted larger than farm area | Error message | |
| 27 | Plantings | Leave expected date empty | Auto-filled from growing period | |
| 28 | Plantings | Filter by status; pagination | 5 pages for 50 rows 📷 | |
| 29 | Harvests | Harvest date before planting date | Error message (and DB trigger) | |
| 30 | Harvests | Choose remark "Good Harvest" | Table shows text; DB `remark_id = 1` 📷 | |
| 31 | Harvests | Negative quantity / text price | Numeric error messages | |
| 32 | Harvests | Filter by grade | Only that grade | |
| 33 | Reports | Each of the 5 reports | Data shown, summary on top 📷 | |
| 34 | Reports | Date-range filter | Rows limited to range | |
| 35 | Reports | Export CSV / Print | CSV downloads; print view hides menus | |
| 36 | Security | Submit form after deleting the CSRF field (browser dev tools) | "Session expired or invalid request" | |
| 37 | Security | Search for `' OR 1=1 --` | No error, no extra rows | |
| 38 | UI | Resize browser to phone width | Menu collapses, tables scroll sideways | |
| 39 | Links | Click every menu link and button | No broken links | |
