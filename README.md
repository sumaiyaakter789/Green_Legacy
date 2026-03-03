# 🌿 GreenLegacy

GreenLegacy is a full-stack eco-focused web platform that integrates e-commerce, plant disease detection, consultancy booking, forum discussions, job portal and real-time communication features into one unified system.

## 🚀 Features

- 🛒 Online Plant Shop (Cart, Orders, Coupons, Inventory)
- 🌱 AI-based Plant Disease Detection (Python & ML Integration)
- 👩‍🌾 Consultant Appointment Booking System
- 💬 Real-time Chat & Video Calling (Socket.io)
- 📝 Blog & Forum System
- 🔁 Plant Exchange Platform
- 💼 Career & Job Management
- 🎁 Reward Points System
- 🔐 Google Authentication & Email Verification
- 📊 Admin Dashboard & Reports

## 🛠 Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **AI Module:** Python (Disease Detection)
- **Real-time Server:** Node.js, Express, Socket.io
- **Email Service:** PHPMailer

## 📂 Project Structure

- `admin_*.php` → Admin Panel Modules  
- `consultant_*.php` → Consultant Dashboard  
- `python/disease_detector.py` → AI Disease Detection Model  
- `signaling-server.js` → Video Call Signaling Server  
- `greenlegacy.sql` → Database File  

## ⚙️ Setup Instructions

1. Import `greenlegacy.sql` into MySQL.
2. Configure database credentials in `db_connect.php`.
3. Start Apache & MySQL (XAMPP/Laragon).
4. Run Node server:
   ```bash
   npm install
   node signaling-server.js
5. Open index.php in a browser

## 👨‍💻 Developed By

- Afnan Shahriar (Team Lead & Backend)
- Mst. Sumaiya Akter (Backend)
- Abir Nag Bulbul (Frontend)
- Mst. Sayma Akter (Frontend)
