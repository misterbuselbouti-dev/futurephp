# Future Automotive - Garage Management System

A comprehensive PHP-based garage management system for automotive workshops and repair centers.

## 🚗 Features

- **Vehicle Management**: Complete vehicle tracking and maintenance records
- **Inventory Management**: Parts and supplies stock management
- **Purchase Orders**: Automated purchase order system (DA, DP, BC, BE)
- **Work Orders**: Service order management and tracking
- **Customer Management**: Customer database and service history
- **Multi-language Support**: French and Arabic language support
- **Driver Portal**: Dedicated interface for drivers
- **Admin Dashboard**: Comprehensive administrative interface
- **API Integration**: RESTful API for mobile/web integration

## 🛠️ Technology Stack

- **Backend**: PHP 8.x
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap
- **Server**: Apache with mod_rewrite
- **Security**: PDO prepared statements, password hashing, XSS protection

## 📋 Requirements

- PHP 8.0 or higher
- MySQL 5.7 or MariaDB 10.2+
- Apache web server with mod_rewrite enabled
- GD library for image processing
- JSON extension enabled

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/futurephp.git
   cd futurephp
   ```

2. **Database Setup**
   - Create a new MySQL database
   - Import the SQL files from the `/sql` directory
   - Update database credentials in `config.php`

3. **Configuration**
   ```bash
   cp config.example.php config.php
   # Edit config.php with your database credentials
   ```

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 logs/
   ```

5. **Configure Apache**
   - Ensure mod_rewrite is enabled
   - Set document root to the project directory
   - Configure .htaccess for clean URLs

## 📁 Project Structure

```
futurephp/
├── api/                 # REST API endpoints
├── admin/              # Administrative interface
├── driver/             # Driver portal
├── management/         # Management modules
├── purchase/           # Purchase management
├── sql/                # Database scripts
├── uploads/            # File uploads
├── logs/               # Application logs
├── assets/             # Static assets
├── config.php          # Main configuration
├── includes/           # Helper functions
└── index.php           # Entry point
```

## 🔧 Configuration

### Database Settings
Update `config.php` with your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### Security Settings
- Set `session.cookie_secure = 1` for HTTPS
- Configure proper file permissions
- Update security headers as needed

## 🌐 API Documentation

The system includes a comprehensive REST API:

- **Authentication**: `/api/auth/login.php`
- **Vehicles**: `/api/cars/`
- **Customers**: `/api/customers/`
- **Inventory**: `/api/inventory/`
- **Work Orders**: `/api/work_orders/`
- **Reports**: `/api/reports/`

## 🌍 Multi-language Support

The system supports French and Arabic languages:

- French (fr): Default language
- Arabic (ar): Right-to-left support

## 🔐 Security Features

- SQL injection protection via PDO prepared statements
- XSS protection with output encoding
- CSRF protection in forms
- Secure password hashing
- Session security measures
- File upload validation

## 📊 Modules

### Purchase Management
- **DA** (Demande d'Achat): Purchase requests
- **DP** (Devis Prix): Price quotes  
- **BC** (Bon de Commande): Purchase orders
- **BE** (Bon de Livraison): Delivery receipts

### Vehicle Management
- Vehicle registration and tracking
- Maintenance history
- Driver assignments
- Breakdown reporting

### Inventory Management
- Parts catalog
- Stock tracking by region
- Supplier management
- Automated reordering

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## ⚠️ Security Notice

**IMPORTANT**: This is a demonstration project. Before using in production:
- Change all default passwords
- Update database credentials
- Configure proper HTTPS
- Review security settings
- Implement proper backup strategies

## 📞 Support

For support and questions:
- Create an issue in the GitHub repository
- Review the documentation
- Check the security audit report

## 🔄 Version History

- **v1.0.0** - Initial release with core functionality
- **v1.1.0** - Added API endpoints and driver portal
- **v1.2.0** - Enhanced security and multi-language support

---

**Future Automotive** - Modern Garage Management Solution
