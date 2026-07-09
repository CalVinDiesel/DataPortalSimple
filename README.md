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
- PostgreSQL (Recommended) or MySQL
- **For Production Server:** Nginx or Apache web server

---

### 2. Local Development Setup (For cloning & testing)
Follow these steps if you are running the system on your personal computer:

1. **Clone and Install Dependencies**
   ```bash
   composer install
   npm install
   ```
2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   *Note: Open the `.env` file and configure your local database credentials. You must also set `VITE_CESIUM_ION_TOKEN` to fully enable the 3D Viewer.*
3. **Database Setup**
   ```bash
   php artisan migrate:fresh --seed
   ```
4. **Run the Development Servers**
   ```bash
   php artisan serve
   npm run dev
   ```

---

### 3. Live Production Server Setup (For Deployment)
Follow these critical steps when deploying the system to a live server (e.g., Ubuntu/Linux) to ensure performance, security, and feature functionality:

1. **Install Dependencies for Production**
   Ensure you don't install development packages and compile the frontend assets:
   ```bash
   composer install --optimize-autoloader --no-dev
   npm install
   npm run build
   ```
2. **Secure Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Open your `.env` file and **strictly set the following**:
   - `APP_ENV=production`
   - `APP_DEBUG=false` *(Critical: Prevents sensitive error logs from leaking)*
   - `APP_URL=https://your-domain.com`
   - Configure Database connections (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
   - Set the `VITE_CESIUM_ION_TOKEN` for the 3D viewer.
   - `BACKUP_DIR=/path/to/your/backups` *(Optional: Custom path for archiving old 3D models during automated housekeeping. If omitted, it defaults to `storage/app/backups` inside your project).*
3. **Database & Cache Initialization**
   Run the migrations and cache the configuration for faster loading:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force
   php artisan optimize
   ```
4. **Set Correct Directory Permissions**
   The web server must be allowed to write to specific directories, or the site will crash with a 500 error:
   ```bash
   sudo chown -R www-data:www-data storage bootstrap/cache
   sudo chmod -R 775 storage bootstrap/cache
   ```
   *(Note: Replace `www-data` with your web server user, such as `nginx`, if applicable).*
5. **Configure the Web Server**
   Ensure your Nginx or Apache **Document Root** points to the `/public` directory of the project, not the root folder.
6. **Set Up Background Tasks (Cron Job)**
   The system has automated daily housekeeping tasks. Add the Laravel scheduler to your server's crontab (`crontab -e`):
   ```text
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   ```
7. **Configure Background Queue Worker (Supervisor)**
   The system processes heavy 3D conversions in the background. To ensure this runs automatically 24/7 on your Linux server, install Supervisor:
   ```bash
   sudo apt-get install supervisor
   ```
   Create a configuration file (`/etc/supervisor/conf.d/3dhub-worker.conf`):
   ```ini
   [program:3dhub-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /path-to-your-project/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopasgroup=true
   killasgroup=true
   user=www-data
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/path-to-your-project/storage/logs/worker.log
   ```
   Then start the worker:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start 3dhub-worker:*
   ```
8. **Firewall Rules (Crucial for SFTP Scans)**
   Because the system performs Deep Scans on external 3D Tilesets via `SftpService.php`, ensure your server's firewall allows **Outbound Connections on Port 22** (or the respective SFTP port of your target servers).
9. **Google Drive Integration**
   To enable real-time file scanning, refer to the **Google Drive Automated Scanner Integration** section below to configure your `google-service-account.json`.

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
