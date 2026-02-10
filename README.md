# Future Automotive - Bus Management System (ISO 9001 Certified)

A comprehensive ISO 9001 certified bus management system for Future Automotive company, featuring professional fleet management, procurement, maintenance tracking, and audit capabilities.

## 🏢 About

Future Automotive is a professional bus management system built with PHP and modern web technologies. The system provides complete fleet management, maintenance tracking, procurement, and audit capabilities with ISO 9001 compliance and a standardized professional design theme.

## ✨ Features

### 🚌 Fleet Management
- Complete bus inventory and tracking
- Driver management and scheduling
- Maintenance scheduling and tracking
- Breakdown reporting and resolution
- Real-time fleet status monitoring

### 🛒 Procurement System
- Purchase request management (DA)
- Price quotation system (DP)
- Purchase order management (BC)
- Goods receipt tracking (BE)
- Supplier management

### 🔧 Maintenance & Operations
- Work order management
- Parts inventory tracking
- Maintenance history
- Technical documentation
- Performance analytics

### 📊 Audit & Compliance
- **ISO 9001 audit system** with complete logging
- Activity logging and tracking
- Compliance reporting
- Quality management
- Performance metrics

### 👥 User Management
- Role-based access control
- User authentication
- Permission management
- Activity monitoring
- Security audit trails

## 🎨 ISO 9001 Design System

The system features a professional ISO 9001 certified design theme:

- **Corporate Colors**: Navy Blue (#1A365D), Anthracite Gray (#2D3748), Forest Green (#22543D)
- **Typography**: Inter font family for professional readability
- **Components**: Standardized ISO components (iso-card, iso-stats-grid, iso-bootstrap)
- **Layout**: Clean, consistent, and professional interface
- **Responsive**: Mobile-friendly design
- **Theme Files**: `iso-theme.css`, `iso-components.css`, `iso-bootstrap.css`

## 🛠️ Technology Stack

- **Backend**: PHP 8.0+
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript
- **Framework**: Bootstrap 5.3
- **Icons**: Font Awesome 6.4
- **Fonts**: Google Fonts (Inter)
- **Architecture**: MVC pattern with modular design

## 📁 Project Structure

```
futurephp/
├── admin/                  # Administrative interfaces
│   ├── audit.php          # Audit management
│   ├── audit_interface.php # Audit interface
│   └── admin_*.php        # Other admin modules
├── assets/                 # Static assets
│   ├── css/               # Stylesheets
│   │   ├── iso-theme.css  # ISO 9001 theme variables
│   │   ├── iso-components.css # ISO components
│   │   └── iso-bootstrap.css # ISO Bootstrap overrides
│   ├── js/                # JavaScript files
│   └── images/            # Images and icons
├── includes/              # PHP includes and components
├── pdf/                   # PDF generation templates
├── sql/                   # Database schemas and migrations
├── *.php                  # Main application files
│   ├── dashboard_iso.php  # ISO 9001 reference design
│   ├── login.php          # Professional login
│   ├── buses.php          # Fleet management
│   ├── achat_*.php        # Procurement modules
│   └── audit_*.php        # Audit system
├── config.php             # Application configuration
└── README.md              # This file
```

## 🚀 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL/MariaDB database
- Web server (Apache/Nginx)
- Composer (for dependencies)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/futurephp.git
   cd futurephp
   ```

2. **Configure database**
   - Create a new database
   - Import SQL files from `/sql/` directory
   - Update database credentials in `config.php`

3. **Configure application**
   - Copy `config.example.php` to `config.php`
   - Update configuration settings
   - Set up file permissions

4. **Install dependencies**
   ```bash
   composer install
   ```

5. **Set up web server**
   - Point document root to project directory
   - Configure URL rewriting
   - Enable SSL (recommended)

6. **Access the application**
   - Open browser to `http://your-domain.com`
   - Log in with default credentials
   - Configure initial settings

## 📋 Database Setup

### Required Tables
- `users` - User management
- `buses` - Fleet information
- `drivers` - Driver data
- `breakdown_reports` - Maintenance issues
- `work_orders` - Work order management
- `demandes_achat` - Purchase requests
- `bons_commande` - Purchase orders
- `audit_logs` - System audit trail

### SQL Scripts
Run the following SQL scripts in order:
1. `sql/database_structure.sql`
2. `sql/sample_data.sql`
3. `sql/admin_user.sql`

## 🔐 Security Features

- **Authentication**: Secure user login system
- **Authorization**: Role-based access control
- **Audit Trail**: Complete activity logging
- **Data Protection**: Input validation and sanitization
- **Session Management**: Secure session handling
- **CSRF Protection**: Cross-site request forgery prevention

## 📊 ISO 9001 Compliance

The system is designed to meet ISO 9001 standards:

- **Quality Management**: Systematic quality control
- **Documentation**: Complete record keeping
- **Audit System**: Regular compliance checks
- **Process Control**: Standardized procedures
- **Continuous Improvement**: Performance monitoring

### Theme Standardization
All pages have been standardized to use the ISO 9001 professional theme:
- ✅ `login.php` - Clean, professional login interface
- ✅ `dashboard.php` - ISO 9001 dashboard design
- ✅ `buses.php` - Fleet management with ISO theme
- ✅ `achat_bc.php`, `achat_da.php` - Procurement with ISO theme
- ✅ `admin/audit*.php` - Audit system with ISO theme
- ✅ `audit_*.php` - Audit reporting with ISO theme

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Development Guidelines
- **Follow ISO 9001 design standards**
- **Maintain code quality standards**
- **Write clear documentation**
- **Test all functionality**
- **Update relevant documentation**
- **Use ISO theme components only**

## 📝 Documentation

- **User Manual**: `/docs/user-guide.md`
- **Admin Guide**: `/docs/admin-guide.md`
- **API Documentation**: `/docs/api.md`
- **Database Schema**: `/docs/database.md`
- **ISO Theme Guide**: `/docs/iso-theme.md`

## 🐛 Bug Reporting

Found an issue? Please report it:

1. Check existing issues
2. Create detailed bug report
3. Include steps to reproduce
4. Add screenshots if applicable
5. Provide system information

## 📞 Support

For support and questions:

- **Email**: support@futureautomotive.com
- **Documentation**: Check `/docs/` directory
- **Issues**: GitHub issue tracker

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🏆 Credits

- **Development**: Future Automotive Team
- **Design**: ISO 9001 Design System
- **Technology**: Modern PHP Stack

## 📈 Version History

- **v2.0.0** - ISO 9001 theme standardization (Current)
- **v1.2.0** - Enhanced audit system
- **v1.1.0** - Added procurement module
- **v1.0.0** - Initial release with core functionality

---

**Future Automotive** - Professional Fleet Management System  
Built with ❤️ for the transportation industry  
🏆 ISO 9001 Certified Design System
