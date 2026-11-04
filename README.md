<div align="center">

# 📱 TECHMOBILE
### *Modern Dockerized E-Commerce Platform*

[![PHP Version](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-Aiven_Cloud-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://aiven.io/)
[![Deployment](https://img.shields.io/badge/Deployed_on-Render-46E3B7?style=for-the-badge&logo=render&logoColor=black)](https://render.com/)
[![Docker](https://img.shields.io/badge/Container-Docker-2496ED?style=for-the-badge&logo=docker&logoColor=white)](https://www.docker.com/)

[🌐 **Explore Live Demo**](https://techmobile.onrender.com)

---

</div>

## 📌 Project Overview

**TECHMOBILE** is a full-featured, responsive e-commerce web application engineered for purchasing mobile devices and accessories. Built with custom **PHP 8.2** and **MySQL**, the application is containerized using **Docker** and deployed with a continuous integration/continuous deployment (CI/CD) pipeline from GitHub directly to **Render**, backed by a production-grade **Aiven Cloud MySQL** database.

---

## ✨ Key Features

* 🛒 **Dynamic Storefront:** Browse catalog items directly synced with cloud database storage.
* 🔐 **Secure User Authentication:** Customer registration, encrypted login, and role-based access control.
* 📦 **Order Management:** Real-time order placement and detailed order status updates (`Pending`, `Completed`, `Cancelled`).
* 🏷️ **Interactive Review System:** Verified customers can submit ratings and feedback for delivered items.
* ⚡ **Automated CI/CD Pipeline:** Instant background deployments via GitHub webhooks on Render upon pushing code updates.

---

## 🛠️ Tech Stack & Architecture

```text
┌────────────────┐      ┌─────────────────────────┐      ┌─────────────────────┐
│   GitHub Repo  │ ---> │     Render Platform     │ ---> │  Aiven Cloud MySQL  │
│  (Source Code) │      │  (Dockerized Apache/PHP)│      │  (Managed Database) │
└────────────────┘      └─────────────────────────┘      └─────────────────────┘
