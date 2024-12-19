# Amena - Package Delivery Management System

**Amena** is a package delivery management platform built with **Symfony**, **Twig**, and **MySQL**. It allows enterprises to manage deliveries, track packages in real-time, and generate QR codes for package tracking. Customers can follow the entire delivery process, from dispatch to final delivery, with real-time status updates.

## Features
- **Real-Time Package Tracking**: Customers can scan QR codes to track packages and view their live status updates.
- **QR Code Generation**: Automatically generates a unique QR code for each package for easy tracking.
- **Delivery Management**: Enterprises can update package statuses, manage deliveries, and assign tasks to delivery personnel.
- **Customer Notifications**: Customers are notified of any status changes via email or SMS.
- **Admin Dashboard**: Admin users can manage deliveries, customers, enterprises, and monitor system activity.
- **Status Updates**: Packages are tracked through various stages, including dispatched, in transit, delivered, and other custom statuses.

## Tech Stack
- **Backend**: Symfony (PHP)
- **Frontend**: Twig (templating engine)
- **Database**: MySQL
- **QR Code Generation**: PHP QR Code Library
- **Authentication**: Symfony Security (JWT or traditional authentication)
- **Deployment**: Docker or hosting platforms supporting PHP and MySQL

## Installation

### Prerequisites
Make sure you have the following installed:
- [PHP](https://www.php.net/downloads) (version 7.4 or higher)
- [Composer](https://getcomposer.org/)
- [MySQL](https://www.mysql.com/)
- [Symfony CLI](https://symfony.com/download)

### Getting Started

1. Clone the repository:
   ```bash
   git clone https://github.com/oussamasah/amena.git
   cd amena
