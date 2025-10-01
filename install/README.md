# WP-POS Simple Installer

A compact, minimal web installer for WP-POS.

## 🚀 Quick Install

1. Upload WP-POS files to your server
2. Visit `https://yoursite.com/install`
3. Follow the 3-step wizard:
   - **Step 1:** Database configuration
   - **Step 2:** App settings & admin account
   - **Step 3:** Install

## 📋 Requirements

- PHP 8.1+
- MySQL 5.7+
- Writable directories: `storage/`, `bootstrap/cache/`, root directory

## 🔧 What It Does

- Creates `.env` file with your settings
- Generates application key
- Runs database migrations
- Seeds initial data
- Creates admin user
- Links storage directory
- Locks installer

## 🛡️ Security

- Input validation
- Secure password handling
- Automatic installer locking
- Session-based state management

## 📁 Files

- `index.php` - Single-file installer (all-in-one)
- `README.md` - This documentation

That's it! Simple and compact.