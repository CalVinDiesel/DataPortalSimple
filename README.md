# 🛰️ SABAH 3DHUB Data Portal

A sleek, simple, and powerful data portal built with Laravel and React for managing 3D flight datasets. This portal allows users to submit project details, and features an integrated 3D viewer for visualizing datasets directly in the browser.

---

## 🚀 Features

- **Project Submissions**: Users can submit project metadata, camera configurations, and requested output formats.
- **Interactive 3D Viewer**: Integrated CesiumJS viewer to visualize 3D models and tilesets, complete with a built-in screenshot capture tool.
- **Smart Link Validation**: Built-in verification for **Google Drive**, **OneDrive**, and **SharePoint** links to ensure they are set to *"Anyone with the link"*.
- **Admin Dashboard**: Manage and review submissions with older-first queues, and manage 3D Tileset links with copy-to-clipboard utilities.
- **Rich UI/UX**: Modern, responsive interface built with React and Tailwind CSS, featuring stable queues and session-based warnings.

---

## 🛠️ Tech Stack

- **Backend**: Laravel 11
- **Frontend**: React 19 & React Router
- **3D Engine**: CesiumJS
- **Styling**: Tailwind CSS
- **Database**: PostgreSQL (Optimized for Neon)
- **Tooling**: Vite & NPM

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
Copy the `.env.example` to `.env` and update your database credentials along with your Cesium Ion token:
```bash
cp .env.example .env
php artisan key:generate
```

*Note: Make sure to set `VITE_CESIUM_ION_TOKEN` in your `.env` file to fully enable the 3D Viewer.*

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

It checks for **Public Accessibility** to prevent submissions with private links that would stall the development pipeline.

---

## 🤖 Google Drive Automated Scanner Integration
Our portal features an automated **"Robot Guard"** that scans the files inside Google Drive folders in real-time *before* allowing submissions to be completed. 

It checks for **naming conventions**, **file size limits**, and **disguised/spoofed formats** (like renaming a `.mp4` file to `.geojson`).

### 🔑 Setting Up the Service Account Key (Real Scanning)

To enable real, live scanning instead of the simulation mode, follow this 2-minute setup:

#### Step 1: Generate the JSON Key in Google Cloud
1. Open the [Google Cloud Console](https://console.cloud.google.com/).
2. Create a free project or select an existing one.
3. Search for **Google Drive API** in the top search bar and click **Enable**.
4. Navigate to **IAM & Admin** > **Service Accounts** in the left sidebar.
5. Click **Create Service Account** at the top. Give it a name (e.g. `Sabah3D Portal Scanner`) and click **Done**.
6. Find your new Service Account in the list, copy its **Email address** (you will need it in Step 3), and click on its name.
7. Click the **Keys** tab at the top, select **Add Key** > **Create New Key**, choose **JSON**, and click **Create**.
   - *This downloads a small `.json` credentials file to your computer.*

#### Step 2: Add the Key to Your Project
1. Rename your downloaded credentials file to exactly: **`google-service-account.json`**
2. Paste this file into the following directory inside your project:
   `storage/app/google-service-account.json`

*Note: The code automatically detects this file. When the file is present, it turns OFF the simulation mode and turns ON real live Google Drive scanning!*

#### Step 3: Grant Folder Permissions
Since the Service Account is a secure "robot" identity, it must be allowed to read the files inside your target Google Drive folder:
- **Option A (Safest & Best):** Open your Google Drive folder, click **Share**, paste your Service Account **Email address** (copied in Step 1) as a **Viewer**, and save.
- **Option B:** Simply share the Google Drive folder as **"Anyone with the link can view"**.

---

## 📄 License
This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
