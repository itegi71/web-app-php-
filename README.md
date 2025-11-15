🩸 Blood Donation Management System - Backend
A robust PHP backend system for managing blood donation operations, donor registrations, and blood inventory tracking.

📋 Table of Contents
Features

Technology Stack

Installation

Configuration

API Documentation

Database Schema

Development

✨ Features
🔐 Authentication & Authorization
JWT-based authentication system

Role-based access control (Admin, Hospital, Donor)

Secure password hashing

Session management

🩺 Donor Management
Donor registration and profile management

Blood type compatibility tracking

Donation history and eligibility

Health screening questionnaires

🏥 Hospital & Blood Bank Management
Hospital registration and verification

Blood inventory management

Request management for blood units

Stock level monitoring and alerts

💉 Donation Process
Appointment scheduling system

Donation camp organization

Real-time blood stock updates

Donation eligibility checks

📊 Analytics & Reporting
Blood stock analytics

Donation statistics

Demand forecasting

Emergency alert system

🛠 Technology Stack
Backend: PHP 8.0+

Framework: Custom MVC Architecture

Database: MySQL 8.0+

Authentication: JWT Tokens

API: RESTful APIs

Security: Input validation, SQL injection prevention

File Storage: Local file system

🚀 Installation
Prerequisites
PHP 8.0 or higher

MySQL 8.0 or higher

Apache/Nginx web server

Composer (for dependencies)

Step-by-Step Setup
Clone the repository

bash
git clone https://github.com/your-org/blood-donation-backend.git
cd blood-donation-backend
Install dependencies

bash
composer install
Database setup

sql
CREATE DATABASE blood_donation;
Environment configuration

bash
cp .env.example .env
Edit .env file with your database credentials and settings.

Run database migrations

bash
php migrate.php
Seed initial data (optional)

bash
php seed.php
Configure web server

Set document root to /public

Enable URL rewriting

⚙️ Configuration
Environment Variables
env
# Database
DB_HOST=localhost
DB_NAME=blood_donation
DB_USER=root
DB_PASS=

# JWT Secret
JWT_SECRET=your-secret-key-here

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8000

# Email (for notifications)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
Directory Structure
text
blood-donation-backend/
├── app/
│   ├── controllers/
│   ├── models/
│   ├── middleware/
│   └── utils/
├── config/
├── public/
│   └── index.php
├── storage/
│   ├── logs/
│   └── cache/
├── tests/
└── vendor/
📚 API Documentation
Base URL
text
https://api.blood-donation.com/v1
Authentication Endpoints
Method	Endpoint	Description	Access
POST	/auth/register	User registration	Public
POST	/auth/login	User login	Public
POST	/auth/logout	User logout	All users
POST	/auth/refresh	Refresh token	All users
Donor Endpoints
Method	Endpoint	Description	Access
GET	/donors/profile	Get donor profile	Donor
PUT	/donors/profile	Update donor profile	Donor
GET	/donors/donations	Get donation history	Donor
POST	/donors/appointments	Schedule appointment	Donor
Hospital Endpoints
Method	Endpoint	Description	Access
GET	/hospitals/inventory	Get blood inventory	Hospital
POST	/hospitals/requests	Create blood request	Hospital
GET	/hospitals/requests	Get blood requests	Hospital
Admin Endpoints
Method	Endpoint	Description	Access
GET	/admin/users	Get all users	Admin
GET	/admin/statistics	Get system statistics	Admin
POST	/admin/camps	Create donation camp	Admin
🗃 Database Schema
Core Tables
users - User accounts and authentication

donors - Donor-specific information

hospitals - Hospital profiles and details

blood_inventory - Blood stock management

donations - Donation records

appointments - Appointment scheduling

blood_requests - Blood request management
