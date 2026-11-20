# 🤖 Agent Implementation & Execution Plan

## About
The Bukit Embun Tourism Camping Ground Service System is a web-based reservation platform designed to digitalize the entire campsite booking and camping equipment rental process. This system enables users to check real-time capacity availability, select specific campsite plots, rent additional gear, pay via a payment gateway, and receive a scannable QR Code e-ticket for instant on-site verification.

## 📌 Agent Architecture Overview

* **Agent Role:** Autonomous System Builder / Full-Stack Engineer Agent.
* **Target Stack:** Laravel (PHP), MySQL, TailwindCSS/Blade, Breeze (Authentication), JavaScript / Alpine.js (Interactive Canvas/SVG Map & QR Code Scanner), Midtrans Payment Gateway.
* **Execution Strategy:** Sequential Phase-based Pipeline with Automated Validation Gates.

---

## 🛠️ Phase 1: Database Architecture & Domain Model Setup

**Objective:** Generate the database schema, migration files, seeders (including stock media metadata), and Eloquent Models representing the reservation business domain.

### Task 1.1: Migration & Schema Design

* **Goal:** Create Laravel migration files matching the entity structure.
* **Entities & Fields Required:**
* `users`: `id`, `name`, `email`, `password`, `phone`, `role` (`admin`, `petugas`, `pengunjung`), `timestamps`.
* `camping_areas` (plots/sites): `id`, `name`, `capacity`, `price_per_night`, `status`, `description`, `layout_coordinates` (JSON storing coordinates, dimensions, and zone info), `timestamps`.
* `facilities` (add-ons): `id`, `name`, `category` (tent/equipment/lamp/etc.), `stock`, `rental_price`, `unit`, `timestamps`.
* `reservations`: `id`, `user_id`, `booking_code`, `camping_area_id`, `check_in_date`, `check_out_date`, `total_price`, `payment_status` (`pending`, `settled`, `expired`, `cancelled`), `snap_token`, `timestamps`.
* `reservation_items` (add-on pivot): `id`, `reservation_id`, `facility_id`, `qty`, `subtotal`, `timestamps`.
* `tickets`: `id`, `reservation_id`, `qr_code_hash`, `is_checked_in`, `checked_in_at`, `checked_in_by`, `timestamps`.
* `galleries` (media assets): `id`, `title`, `category` (`hero`, `camping_area`, `ambiance`, `facilities`), `image_url`, `thumbnail_url`, `photographer_name`, `source_provider` (`pexels`, `pixabay`, `unsplash`), `external_id`, `is_featured` (boolean), `timestamps`.



### Task 1.2: Eloquent Model, Relationships & Seeders Definition

* **Model Relationships:**
* `User` `hasMany` `Reservation`.
* `Reservation` `belongsTo` `CampingArea`, `belongsTo` `User`, `hasMany` `ReservationItem`, `hasOne` `Ticket`.
* `ReservationItem` `belongsTo` `Facility`.


* **Database Seeding (`GallerySeeder.php` & `CampingAreaSeeder.php`):**
* Populate `camping_areas` with realistic spatial `layout_coordinates` JSON (e.g., `{"x": 120, "y": 80, "width": 60, "height": 40, "zone": "Sunrise Ridge"}`).
* During `php artisan db:seed`, fetch curated nature/camping photos via the Pexels/Pixabay API (using `PEXELS_API_KEY` or `PIXABAY_API_KEY` from `.env`) or seed verified high-resolution stock photo URLs directly into the `galleries` table so the application renders immediately without broken images or client-side API rate limits.



### 🛡️ Validation Gate (Phase 1):

* The agent runs `php artisan migrate:fresh --seed` without errors.
* Perform relationship and seeded media assertions via `php artisan tinker`.

---

## ⚙️ Phase 2: Core Logic & Booking Engine Development

**Objective:** Build the Service Layer for real-time quota calculation, transaction locking, and total price calculation.

### Task 2.1: Quota & Availability Service (`BookingService`)

* **Logic Requirements:**
* `checkAvailability($checkInDate)` function: Calculates total active reservations (`settled` & unexpired `pending`) on that date, as well as plot-specific availability for the interactive map.
* If (Active Reservations Count >= Maximum Daily Capacity) or the selected `camping_area_id` is already reserved for the overlapping date range, return `false`.
* Prevent *race conditions* or *overbooking* when two users check out simultaneously (Use Database Locks / Transaction Isolation Levels).



### Task 2.2: Add-On & Cart Calculation Engine

* **Logic Requirements:**
* Total Calculation = `(Camping Area Price x Number of Nights)` + `Sum(Equipment Price x Qty)`.
* Verify physical equipment stock availability in `facilities.stock` before creating the reservation.

### 🛡️ Validation Gate (Phase 2):

* The agent creates a Unit Test (`BookingServiceTest.php`) that verifies:
1. Rejection of reservations when the date's quota or specific plot is full.
2. Accuracy of total price calculations including multi-night stays and add-ons.

---

## 💳 Phase 3: Payment Gateway & Ticket Generator Automation

**Objective:** Integrate the Midtrans Snap API and automated QR-coded e-ticket generation.

### Task 3.1: Midtrans Service Integration (`PaymentService`)

* **Logic Requirements:**
* Checkout Endpoint: Generate a `snap_token` from the Midtrans Snap API using the order payload (`order_id`, `gross_amount`, `customer_details`).
* Webhook Handler Endpoint (`POST /api/midtrans/notification`):
* Receive callback payloads from Midtrans.
* Verify the `signature_key` from Midtrans.
* If `transaction_status` == `settlement`/`capture`, update `reservations.payment_status` $\rightarrow$ `settled`.
* Invoke `TicketService` to automatically issue and record the e-ticket.





### Task 3.2: QR Code & Ticket Generation (`TicketService`)

* **Logic Requirements:**
* Generate a unique `qr_code_hash` (e.g., `SHA256(booking_code + timestamp + secret)`).
* Create a record in the `tickets` table.
* Provide a helper function to render the ticket into PDF format / Blade View with an integrated QR Code component.



### 🛡️ Validation Gate (Phase 3):

* The agent simulates a Midtrans callback notification via a Feature Test (`MidtransWebhookTest.php`).
* Assertion: The reservation status updates to `settled` and a `tickets` record is automatically created.

---

## 📱 Phase 4: UI Build, Nature Aesthetic & Role-Based Workflows

**Objective:** Render an immersive, nature-inspired user interface tailored to each role (Visitor, Staff, Admin).

### Task 4.1A: Visual Theme & "Immersed in Nature" Design System

* **Color Palette (TailwindCSS Configuration):**
* **Primary:** Deep Forest Green (`#1B4332` / `emerald-900`) & Pine Green (`#2D6A4F`).
* **Secondary / Accents:** Warm Earth / Terracotta (`#BC6C25` / `amber-700`) for primary CTAs (e.g., "Book Now") and Golden Hour Amber (`#DDA15E`) for highlights.
* **Backgrounds & Surfaces:** Soft Mist / Off-White (`#F8F9FA` / `stone-50`) and Warm Sand (`#FEFAE0`) instead of stark `#FFFFFF`, paired with subtle glassmorphism (`backdrop-blur-md bg-white/80`) on floating cards.


* **Typography & Organic Shapes:**
* Pair a refined serif font for headings (e.g., *Playfair Display* or *Lora* via Google Fonts) with a clean sans-serif for UI controls (e.g., *Plus Jakarta Sans* or *Inter*).
* Use generous border radii (`rounded-2xl`), soft ambient shadows, and organic SVG wave/mountain dividers between page sections.



### Task 4.1B: Visitor Portal — Nature Landing Page & Database-Backed Gallery

* **Hero Section:** Full-viewport (`min-h-screen`) atmospheric nature banner pulled from `galleries` (`category = 'hero'`), featuring a dark gradient overlay, mist/forest typography, and a floating glassmorphic **Quick Availability Search Bar** (Check-in Date, Check-out Date, Guest Count).
* **Value Proposition & Facilities Showcase:** Bento-grid layout highlighting the Bukit Embun experience (sunrise views, stargazing, tent rentals) with subtle scroll-reveal transitions.
* **Dynamic Photo Gallery:** Masonry or horizontal showcase grid querying the `galleries` table directly (grouped by category), complete with a lightbox modal and photographer attribution.
* **My Tickets / Transaction History:** Displays transaction history & downloadable/viewable QR-coded e-tickets.

### Task 4.1C: Visitor Portal — Interactive "Cinema-Style" Camping Plot Selector & Checkout

* **Architecture:** Build a reactive 2D interactive campsite map component (using HTML5 `<svg>` or `<canvas>` + Alpine.js / Vanilla JS) on the booking page.
* **Coordinate & Landmark Rendering:**
* Read `layout_coordinates` JSON from `camping_areas` to position each plot accurately.
* Render environmental landmarks on the map (hiking trails, campfire pit, restrooms, cliff/sunrise viewpoint, tree clusters) so users understand spatial orientation.


* **Interactive States & Legend:**
* **Available (Forest Green):** Hovering reveals a floating tooltip with plot name, capacity, elevation/view notes, and `price_per_night`. Clicking selects the plot and updates the sticky booking summary panel in real time.
* **Booked / Pending (Muted Slate/Red with diagonal hatch):** Disabled cursor (`cursor-not-allowed`), non-clickable for the selected dates.
* **Selected (Warm Amber / Gold glow):** Highlights the active plot and dynamically recalculates the total price alongside selected add-ons.


* **Mobile & Accessibility Support:** Support pinch-to-zoom / horizontal pan on smaller viewports, plus a synchronized dropdown/card list fallback below the map.
* **Add-on Selector & Midtrans Popup:** Interactive quantity steppers for rental equipment connected to the sticky summary panel, triggering the Midtrans Snap JS Popup on checkout.

### Task 4.2: Staff (`Petugas`) Scanner Interface

* **Views & Functionality:**
* `/petugas/scan` page protected by the `role:petugas` middleware.
* Integration of a JavaScript Camera Scanner Library (such as `html5-qrcode`).
* AJAX Request to `POST /api/tickets/verify`:
* If the QR is valid & `is_checked_in == false`: Update `is_checked_in = true`, record `checked_in_at`, and display a SUCCESS modal (showing visitor name details, assigned camping plot, & list of rented equipment).
* If the QR is invalid or already used: Display an ERROR modal.





### Task 4.3: Admin Control Panel

* **Views:**
* Camping Plot (`layout_coordinates`, status, pricing) & Equipment Stock Management CRUD.
* Gallery Asset Management CRUD (add/manage stock photo URLs and featured flags in `galleries`).
* Real-time Transaction Monitoring & Report Exports.



### 🛡️ Validation Gate (Phase 4):

* End-to-end browser testing (Dusk/Cypress) simulating the flow: Visitor selects dates $\rightarrow$ clicks an available plot on the interactive map $\rightarrow$ completes Midtrans checkout $\rightarrow$ Staff scans the generated QR Code.

---

## 🧪 Phase 5: Automated QA & Security Audit Execution

**Objective:** Automated testing of code quality, performance, and security vulnerabilities by the agent.

### Task 5.1: Black-Box & Integration Test Execution

* The agent executes a Black-Box testing suite:
* **Test Case A:** Login Form Submission (Valid, Invalid, Empty Fields).
* **Test Case B:** Date Selection, Interactive Map Availability State & Quota Boundary Value Testing (Full Date / Booked Plot).
* **Test Case C:** QR Code Scan Verification (Fake Token, Valid Token, Re-scanning the Same Token).



### Task 5.2: Security & Performance Sanity Check

* Check for SQL Injection vulnerabilities in data searches.
* Ensure Authorization Middleware on Staff (`petugas`) and Admin routes is properly configured.
* Optimize N+1 queries when fetching transaction lists, reservation items, and gallery assets.

---

## 📊 Agent Task Matrix & Dependency Graph

```
[Phase 1: DB, Models & Gallery Seeder]
                  │
                  ▼
      [Phase 2: Booking Engine]
                  │
         ┌────────┴────────┐
         ▼                 ▼
[Phase 3: Midtrans   [Phase 4.1: Nature UI
   & QR Engine]        & Interactive Map]
         │                 │
         └────────┬────────┘
                  ▼
    [Phase 4.2 & 4.3: Staff & Admin UI]
                  │
                  ▼
     [Phase 5: Full QA & Audit Gate] ➔ [Deployment Ready]

```

---

## 📝 Directives & System Rules for AI Execution

> **STRICT EXECUTION DIRECTIVES FOR THE AI AGENT:**
> 1. **No Hardcoded Credentials:** All Midtrans and Stock Photo API keys (`MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `PEXELS_API_KEY`, `PIXABAY_API_KEY`) must be read via the environment file (`.env`).
> 2. **Transactional Integrity:** All processes creating reservations and their items MUST use `DB::beginTransaction()` and `DB::commit()`.
> 3. **QR Code Security:** QR Code tokens must not contain raw auto-increment IDs (e.g., `id=12`), and must instead use an *encrypted string* or *secure hash*.
> 4. **API Response Format:** All API endpoints for QR Code scanning must respond in a standard JSON format: `{ "success": boolean, "message": string, "data": object|null }`.
> 5. **No Live Client-Side Stock API Calls:** The frontend Blade views must NEVER call Pexels/Pixabay APIs directly via client-side JavaScript. All gallery and hero images must be served from the local `galleries` database table.
> 6. **Interactive Map Accessibility:** Every clickable camping plot on the interactive SVG/Canvas map must carry `data-area-id`, `data-status`, and keyboard/ARIA attributes, and must synchronize bidirectionally with the reservation form inputs.
>
>

### Architectural Revision Plan: Bukit Embun v2.0

This revision upgrades the system to support **30+ campsite plots**, **full Admin CRUD with local image uploads**, and **multi-plot group reservations** within a single Midtrans transaction and e-ticket flow.

---

### Phase R1: Database Schema & Model Refactor (Multi-Plot Support)

**Objective:** Allow a single `Reservation` to span multiple `CampingArea` plots when a group's `guest_count` exceeds a single plot's capacity, while preserving historical price snapshots per plot.

1. **Migration (`create_reservation_areas_table` & alter `reservations`):**
* Create `reservation_areas` pivot table: `id`, `reservation_id` (FK), `camping_area_id` (FK), `area_price_snapshot` (`decimal(12,2)`), `subtotal` (`decimal(12,2)`), `timestamps`, plus unique index `['reservation_id', 'camping_area_id']`.
* Make `reservations.camping_area_id` and `reservations.area_price_snapshot` nullable (or keep `camping_area_id` as primary/first plot for backward compatibility while treating `reservation_areas` as the source of truth).


2. **Eloquent Models & Seeders:**
* Add `ReservationArea` model (`belongsTo` `Reservation`, `belongsTo` `CampingArea`).
* Add `Reservation::areas()` (`hasMany` `ReservationArea`) and `CampingArea::reservationAreas()` (`hasMany` `ReservationArea`).
* Expand `CampingAreaSeeder` to seed **32 realistic plots** across 4 zones (*Sunrise Ridge*, *Pine Grove*, *Mist Valley*, *Starlight Plateau*) distributed cleanly across a `1200 x 800` coordinate grid.



---

### Phase R2: Multi-Plot Booking Engine, Midtrans & QR Ticket Updates

**Objective:** Atomically lock and reserve multiple selected plots (`camping_area_ids[]`) in one transaction, validate combined capacity against `guest_count`, and display all booked plots on the Midtrans invoice, E-Ticket, and Gate Scanner.

1. **`BookingService` Upgrade:**
* Accept `camping_area_ids` (array of 1..N plot IDs).
* Sort `camping_area_ids` ascending before `lockForUpdate()` to prevent deadlocks during concurrent multi-plot checkouts.
* Validate that **every** selected plot is `available` and has zero date overlap in `reservation_areas` (for `settled` or unexpired `pending` reservations).
* Validate combined capacity: `SUM(selectedPlots.capacity) >= guest_count`.
* Calculate `total_price = SUM(plot.price_per_night * duration_nights) + SUM(facility.rental_price * qty)`.
* Increase default `CAMPING_MAX_DAILY_RESERVATIONS` in `config/camping.php` from `20` to `50`.


2. **`PaymentService` & `ScannerController` Updates:**
* Send itemized plot line items (`AREA-{id}`) to Midtrans Snap `item_details`.
* Update `ScannerController::verify()` and `tickets/e-ticket.blade.php` to list all assigned plots for group reservations.


3. **Validation Gate:** Update `BookingServiceTest` to verify multi-plot booking, combined capacity validation, partial plot conflict rollback, and deadlock-safe locking.

---

### Phase R3: Expanded 1200×800 Interactive Canvas & Multi-Select Visitor UI

**Objective:** Scale the interactive SVG map to comfortably house 30+ plots with zoom/pan/zone filtering, visual plot-placement preview, and multi-plot cart selection.

1. **Large-Scale SVG Map (`viewBox="0 0 1200 800"`):**
* Add zone quick-jump filters (*All Zones*, *Sunrise Ridge*, *Pine Grove*, *Mist Valley*, *Starlight Plateau*) and zoom controls (`100%`, `125%`, `150%`) + smooth drag/scroll panning.
* Render topographic paths, trail networks, multiple washroom blocks, campfire amphitheater, sunrise cliff, and 30+ plot nodes.
* Add hover/focus floating tooltip showing plot name, zone, elevation, capacity, and nightly rate.


2. **Multi-Plot Selection & Capacity Gauge:**
* Clicking an available plot toggles it in `selectedPlotIds[]` (highlighted in Golden Hour Amber).
* Display a live **Group Capacity Meter** in the sticky summary panel (e.g., *"Selected Capacity: 8 / 6 Guests"* — turning green once sufficient, or warning if more plots are needed).



---

### Phase R4: Full Admin CRUD & Local Gallery Image Upload

**Objective:** Complete Create, Update, and Delete workflows for Camping Plots (with interactive coordinate picker), Equipment/Facilities, and Gallery Photos (supporting direct file upload via `storage/app/public/galleries` as well as external URLs).

1. **Camping Plot CRUD (`AdminDashboardController` + Modal/Drawer UI):**
* **Create & Delete** endpoints (`POST /admin/areas`, `DELETE /admin/areas/{campingArea}` — guarding against deleting plots with active/historical reservations).
* Add an **Interactive Coordinate Picker** in the Admin Plot Modal: clicking anywhere on a mini `1200×800` SVG preview auto-fills `x` and `y` coordinates so admins never guess pixel positions.


2. **Equipment (`Facilities`) CRUD:**
* Wire up **Create** (`POST /admin/facilities`) and **Delete** (`DELETE /admin/facilities/{facility}`) forms in the Admin Equipment tab alongside inline stock/price updates.


3. **Gallery File Upload (`storage:link`):**
* Update `storeGallery` (and add `updateGallery`) to accept an uploaded image file (`image_file` — `jpg,png,webp`, max `5MB`) stored via `$request->file('image_file')->store('galleries', 'public')`, automatically generating `image_url` and `thumbnail_url` via `Storage::url(...)`, while still keeping optional external URL support.
* Clean up stored local files upon gallery deletion in `destroyGallery`.



---
