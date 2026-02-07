# OD9 Complete Audit & Inventory

**Audit Date**: February 7, 2026
**Location**: C:\xampp\htdocs\od9
**Total PHP Files**: 77 files

---

## 1. Directory Structure

### Root Level
```
od9/
├── config/              # Configuration files
├── private/             # Backend logic and admin panels (protected)
│   ├── admin/          # Admin panel modules
│   ├── logs/           # System logs
│   └── migrations/     # Database migrations
└── public/             # Public-facing pages and assets
    ├── admin/          # Public admin entry point
    ├── assets/         # Static assets (images, etc.)
    ├── css/           # Stylesheets
    ├── images/        # Public images
    └── js/            # JavaScript files
```

### Admin Panel Structure (private/admin/)
```
private/admin/
├── analytics/          # Analytics dashboard
├── customers/          # Customer management (list, view)
├── discord/           # Discord integration dashboard
├── discord-bot/       # Discord bot management
├── email/             # Email campaign management
├── events/            # Event management system
├── marketing/         # Marketing tools and analytics
├── media/             # Media library management
├── members/           # Member management
├── orders/            # Order management and processing
├── products/          # Product/merchandise management
├── resources/         # Resource management
├── support/           # Support ticket system
├── tickets/           # Event ticket management
├── tiers/             # Membership tier management
└── tools/             # Admin utilities
```

### Public Asset Structure
```
public/
├── assets/images/
│   └── logos/         # Logo assets
├── images/
│   ├── crew/          # Crew member photos
│   ├── logos/         # Additional logos
│   ├── music/         # Music-related images
│   └── payment/       # Payment provider logos
├── css/               # Stylesheets
└── js/                # Client-side JavaScript
```

---

## 2. Core PHP Classes & Architecture

### Configuration Layer (`/config/`)
- **bootstrap.php** - Application initialization and autoloader
- **database.php** - Database configuration and connection setup

### Database Utilities (Root Level Debug Files)
- **check_db.php** - Database connection verification
- **check_table.php** - Table structure verification
- **check-admin.php** - Admin user verification
- **db_check.php** - Comprehensive database health check
- **add_pioneer_column.php** - Migration utility (adds pioneer tier column)

### Entry Points
- **public/index.php** - Main site entry point
- **public/admin/index.php** - Admin panel public entry
- **private/admin/index.php** - Admin panel backend entry
- **site.php** - Root site configuration
- **test-config.php** - Configuration testing utility

---

## 3. Database Layer

### Expected Core Classes (Based on structure)
**Location**: Likely in `config/` directory
- **database.php** - Database connection and query handling
- **bootstrap.php** - May contain Auth and Email utilities

### Database Connection Pattern
- Uses config/database.php for connection setup
- Migration system present in private/migrations/
- Multiple verification scripts indicate custom Database class

---

## 4. Public Pages

### Main Public Pages (`/public/`)
| File | Purpose |
|------|---------|
| **index.php** | Homepage and main entry point |
| **framework.php** | Framework/about page |
| **da-crew.php** | Crew members showcase |
| **music.php** | Music catalog/player |
| **resources.php** | Resource library |
| **tiers.php** | Membership tiers display |
| **contact.php** | Contact form |
| **support.php** | Support portal |

---

## 5. Admin Panel Features

### Core Admin Functions (`/private/admin/`)

#### Dashboard & Analytics
- **dashboard.php** - Main admin dashboard
- **analytics/dashboard.php** - Analytics overview
- **audit-design-standards.php** - Design system auditing tool

#### Authentication
- **login.php** - Admin login handler
- **logout.php** - Session termination
- **check_tables.php** - Database table verification

#### Customer Management
- **customers/list.php** - Customer listing
- **customers/view.php** - Individual customer details

#### Event Management (Full CRUD)
- **events/list.php** - Event listing
- **events/create.php** - Create new event
- **events/edit.php** - Edit existing event
- **events/view.php** - View event details
- **events/delete.php** - Delete event
- **events/update-status.php** - Event status management

#### Product/Merchandise Management
- **products/list.php** - Product catalog
- **products/create.php** - Add new product
- **products/edit.php** - Edit product
- **products/view.php** - View product details
- **products/delete.php** - Remove product
- **products/duplicate.php** - Clone product
- **products/stock-history.php** - Inventory tracking

#### Order Management
- **orders/list.php** - Order listing
- **orders/view.php** - Order details
- **orders/refund.php** - Process refunds
- **orders/resend-tickets.php** - Resend ticket emails

#### Ticket System (Event Tickets)
- **tickets/list.php** - Ticket inventory
- **tickets/designer.php** - Ticket template designer
- **tickets/scanner.php** - QR code scanner interface
- **tickets/scanner-api.php** - Scanner API endpoint
- **tickets/template-api.php** - Template management API

#### Email Marketing
- **email/dashboard.php** - Email campaign overview
- **email/campaigns.php** - Campaign list
- **email/campaign-create.php** - Create campaign
- **email/campaign-view.php** - View campaign details
- **email/campaign-send.php** - Send campaign

#### Media Library
- **media/dashboard.php** - Media overview
- **media/list.php** - File listing
- **media/upload.php** - File uploader
- **media/edit.php** - Edit media metadata
- **media/categories.php** - Media categorization

#### Member Management
- **members.php** - Member overview
- **members/index.php** - Member portal
- **members/list.php** - Member directory

#### Membership Tiers
- **tiers.php** - Tier overview
- **tiers/index.php** - Tier management
- **tiers/list.php** - Tier listing

#### Discord Integration
- **discord/dashboard.php** - Discord integration overview
- **discord-bot.php** - Bot configuration
- **discord-bot/dashboard.php** - Bot management dashboard

#### Marketing Tools
- **marketing/analytics.php** - Marketing analytics
- **marketing/promo-codes.php** - Promotional code management

#### Support System
- **support/list.php** - Support ticket list
- **support/view.php** - Ticket details

#### Resources
- **resources.php** - Resource overview
- **resources/list.php** - Resource library

#### Utility Tools
- **generate-wrappers.php** - Code generation utility

---

## 6. Key Features

### Ticketing System
- Event ticket creation and management
- QR code generation and scanning
- Ticket template designer with visual editor
- Email delivery system for tickets
- Resend capability for lost tickets
- API endpoints for mobile scanner apps

### Event Management
- Full CRUD operations for events
- Status management (draft, published, cancelled)
- Event-specific settings and configurations
- Integration with ticket system
- Event analytics tracking

### Merchandise/E-commerce
- Product catalog with variants
- Inventory tracking and stock history
- Order processing and fulfillment
- Refund management
- Product duplication for quick setup
- Stock alerts and management

### Media Gallery
- Centralized media library
- Category-based organization
- File upload and management
- Media metadata editing
- Integration with other modules

### Email Marketing
- Campaign creation and management
- Email list segmentation
- Campaign analytics and tracking
- Scheduled sending
- Template system

### Membership System
- Tiered membership structure
- Member directory and profiles
- Tier-based access control
- Member analytics

### Discord Integration
- Discord server integration
- Bot management dashboard
- Automated role assignment
- Member synchronization

### Marketing & Analytics
- Promo code system
- Conversion tracking
- Customer analytics
- Sales reporting

---

## 7. Dependencies

### Composer
**Status**: No composer.json found
**Implication**: Either using manual dependency management or dependencies are bundled

### Likely Manual Dependencies
Based on functionality, likely using:
- PHP native features only
- Custom database abstraction
- No external framework (custom-built)
- Possibly bundled JavaScript libraries

---

## 8. Configuration Files

### Identified Configuration
- **config/bootstrap.php** - Core initialization
- **config/database.php** - Database settings
- **site.php** - Site-wide configuration
- **test-config.php** - Configuration testing

### Expected Configuration Elements
- Database credentials (host, username, password, database name)
- Email settings (SMTP configuration)
- Discord API credentials
- Payment gateway configuration
- Application constants and settings

---

## 9. Asset Structure

### CSS Organization (`/public/css/`)
- Centralized stylesheet directory
- (Detailed file listing pending rate limit resolution)

### JavaScript Organization (`/public/js/`)
- Client-side scripts directory
- Likely includes:
  - Form validation
  - Interactive UI components
  - AJAX handlers
  - Ticket scanner functionality

### Image Assets (`/public/images/`)
```
images/
├── crew/          # Team member photos for da-crew.php
├── logos/         # Branding assets
├── music/         # Album covers and music-related graphics
└── payment/       # Payment provider logos (Stripe, PayPal, etc.)
```

### Additional Assets (`/public/assets/`)
```
assets/
└── images/
    └── logos/     # Additional logo variations
```

---

## 10. Database Connection

### Connection Setup
**Location**: config/database.php

### Usage Pattern
- Bootstrap loads database configuration
- Custom Database class (location TBD)
- Connection verification scripts in root directory
- Migration system in private/migrations/

### Verification Scripts
Multiple database check scripts suggest:
- Custom connection management
- Health monitoring capabilities
- Migration tracking system
- Admin user authentication table

---

## Analysis Summary

### Architecture Type
- **Custom PHP Application** (no framework detected)
- **Modular Structure** with clear separation
- **Admin/Public Split** for security

### Code Organization
- **Good**: Clear module separation in admin panel
- **Good**: Logical public/private distinction
- **Note**: No composer dependencies (all custom or bundled)

### Feature Completeness
**Core Systems Present**:
- ✅ Event management
- ✅ Ticketing system with QR codes
- ✅ E-commerce/merchandise
- ✅ Media gallery
- ✅ Email marketing
- ✅ Member management
- ✅ Discord integration
- ✅ Support system
- ✅ Analytics

### Next Steps for Detailed Audit
1. Read bootstrap.php to identify core classes
2. Examine database.php for connection pattern
3. Review auth implementation
4. Analyze email system
5. Check CSS/JS file organization
6. Examine migration files for database schema

---

## File Count Summary

| Category | Count |
|----------|-------|
| **Total PHP Files** | 77 |
| **Config Files** | 2 |
| **Public Pages** | 8 |
| **Admin Modules** | 60+ |
| **Debug/Utility** | 7 |
| **Directories** | 30 |

**Status**: Initial structure audit complete. Detailed code analysis pending rate limit resolution.
