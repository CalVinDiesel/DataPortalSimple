# 🛰️ 3DHub Data Portal

A sleek, simple, and powerful data portal built with Laravel for managing 3D flight datasets. This portal allows users to submit project details and dataset links while ensuring all shared storage is accessible for processing.

---

## 🚀 Features

- **Project Submissions**: Users can submit project metadata, camera configurations, and requested output formats.
- **Smart Link Validation**: Built-in verification for **Google Drive**, **OneDrive**, and **SharePoint** links to ensure they are set to *"Anyone with the link"*.
- **Admin Dashboard**: Manage and review submissions through a professional administrative interface.
- **Rich UI/UX**: Modern glassmorphism design with responsive components.

---

## 🛠️ Tech Stack

- **Framework**: Laravel 11
- **Styling**: Vanilla CSS (Custom Design System)
- **Database**: PostgreSQL (Optimized for Neon)
- **Frontend Tooling**: Vite & NPM

---

## 📦 Installation & Setup

### 1. Prerequisites
- PHP 8.2 or higher
- Composer
- Node.js & NPM

### 2. Clone and Install
```bash
# Install PHP dependencies
composer install

# Install JS dependencies
npm install
npm run build
```

### 3. Environment Configuration
Copy the `.env.example` to `.env` and update your database credentials:
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database Setup
Once your database is connected, initialize the structure and seed the default accounts:
```bash
php artisan migrate:fresh --seed
```

---

## 🔐 Default Credentials

The system comes pre-seeded with two accounts for testing:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Administrator** | `admin@3dhub.com` | `password` |
| **Regular User** | `user@3dhub.com` | `password` |

---

## 📂 Cloud Storage Support
The portal automatically validates links from the following providers during submission:
- ✅ **Google Drive**
- ✅ **Microsoft OneDrive**
- ✅ **SharePoint**

It checks for **Public Accessibility** to prevent submissions with private links that would stall the processing pipeline.

---

## 📄 License
This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
