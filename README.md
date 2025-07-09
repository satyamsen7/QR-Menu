# QR Code Multi-Vendor Restaurant Menu System

A comprehensive web-based system where restaurants, hotels, or food vendors can create digital menus linked to QR codes. Customers scan the QR code to view a vendor's menu with no ordering functionality.

## 🌟 Features

### Core Features
- **User Registration & Authentication**
  - Phone/Email registration with toggle option
  - Google SSO integration (ready for implementation)
  - Remember me functionality
  - Secure password hashing

- **Vendor Setup Wizard**
  - Business profile creation
  - Logo upload (PNG, max 200KB)
  - Custom username or auto-generation
  - Address management

- **Dynamic Menu Builder**
  - Unlimited categories and items
  - Real-time editing
  - Price management with ₹ symbol
  - Drag-and-drop reordering (ready for implementation)

- **QR Code Generation**
  - Multiple QR code styles (Default, Rounded, Colored)
  - Download as PNG (256x256, 512x512)
  - Print options (Sticker, Poster sizes)
  - Analytics tracking

- **Public Menu Display**
  - Clean, responsive design
  - Category-based menu organization
  - Share functionality (WhatsApp, Telegram)
  - Mobile-optimized

- **Analytics & Tracking**
  - QR scan tracking with IP and user agent
  - Daily, weekly, monthly statistics
  - Dashboard with key metrics

### Technical Features
- **Clean URL Routing** - No .php extensions in frontend
- **RESTful API Structure** - Clean separation of concerns
- **Responsive Design** - Works on all devices
- **Security Features** - SQL injection prevention, XSS protection
- **Database Optimization** - Efficient queries and indexing

## 🛠️ Technology Stack

- **Frontend**: HTML5, CSS3, JavaScript, Tailwind CSS
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **QR Code**: QRCode.js library
- **Icons**: Font Awesome 6
- **Fonts**: Google Fonts (Inter)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for clean URLs)
- GD extension for image processing

## 🚀 Installation

### 1. Database Setup

1. Create a MySQL database
2. Import the `database.sql` file:
   ```bash
   mysql -u root -p < database.sql
   ```

### 2. Configuration

1. Update database connection in `config/database.php`:
   ```php
   private $host = 'localhost';
   private $db_name = 'qr_menu_system';
   private $username = 'your_username';
   private $password = 'your_password';
   ```

2. Update the base path in `index.php`:
   ```php
   $base_path = '/your-project-folder/'; // Adjust based on your setup
   ```

### 3. File Permissions

Ensure the uploads directory is writable:
```bash
chmod 755 uploads/
chmod 755 uploads/logos/
```

### 4. Web Server Configuration

#### Apache (.htaccess already included)
The `.htaccess` file is already configured for clean URLs.

#### Nginx
Add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 📁 Project Structure

```
QR-Menu/
├── config/
│   └── database.php          # Database configuration
├── includes/
│   ├── header.php           # Common header template
│   └── footer.php           # Common footer template
├── pages/
│   ├── home.php             # Landing page
│   ├── register.php         # User registration
│   ├── login.php            # User login
│   ├── setup.php            # Vendor setup wizard
│   ├── dashboard.php        # Vendor dashboard
│   ├── menu-builder.php     # Menu creation/editing
│   ├── qr-generator.php     # QR code generation
│   ├── public-menu.php      # Public menu display
│   └── 404.php              # Error page
├── uploads/
│   └── logos/               # Logo uploads
├── .htaccess                # URL rewriting rules
├── index.php                # Main router
├── logout.php               # Logout functionality
├── database.sql             # Database schema
└── README.md                # This file
```

## 🔧 Configuration Options

### Google reCAPTCHA
Update the site key in registration and login pages:
```html
<div class="g-recaptcha" data-sitekey="YOUR_SITE_KEY"></div>
```

### Google OAuth (Future Implementation)
Add Google OAuth credentials for SSO functionality.

### Custom Domain
Update the menu URL in `qr-generator.php`:
```php
$menu_url = "https://yourdomain.com/" . $vendor['username'];
```

## 📊 Database Schema

### Tables
- **users** - User accounts and authentication
- **vendors** - Business information and setup status
- **menu_categories** - Menu category organization
- **menu_items** - Individual menu items with prices
- **qr_scans** - QR code scan tracking and analytics
- **user_sessions** - Remember me functionality

## 🔒 Security Features

- **SQL Injection Prevention** - Prepared statements
- **XSS Protection** - Output escaping
- **CSRF Protection** - Form validation
- **Password Security** - bcrypt hashing
- **Session Security** - Secure session management
- **File Upload Security** - Type and size validation

## 📱 Mobile Responsiveness

The system is fully responsive and optimized for:
- Mobile phones
- Tablets
- Desktop computers
- All modern browsers

## 🎨 Customization

### Styling
- Modify Tailwind CSS classes in templates
- Update color scheme in `includes/header.php`
- Custom CSS in the style section

### Branding
- Update logo and colors
- Modify business information
- Custom email templates (future)

## 🚀 Deployment

### Production Checklist
1. Update database credentials
2. Set proper file permissions
3. Configure SSL certificate
4. Update domain settings
5. Set up backup system
6. Configure error logging

### Performance Optimization
- Enable PHP OPcache
- Configure MySQL query cache
- Use CDN for static assets
- Implement image optimization

## 🔧 Troubleshooting

### Common Issues

1. **Clean URLs not working**
   - Ensure mod_rewrite is enabled
   - Check .htaccess file permissions
   - Verify base path in index.php

2. **Database connection errors**
   - Verify database credentials
   - Check MySQL service status
   - Ensure database exists

3. **File upload issues**
   - Check upload directory permissions
   - Verify PHP upload settings
   - Check file size limits

4. **QR code not generating**
   - Ensure QRCode.js is loaded
   - Check browser console for errors
   - Verify URL format

## 📈 Analytics & Monitoring

The system tracks:
- Total QR scans
- Daily/weekly/monthly scan trends
- User engagement metrics
- Menu performance data

## 🔮 Future Enhancements

- **Ordering System** - Add online ordering functionality
- **Payment Integration** - Payment gateway integration
- **Inventory Management** - Stock tracking
- **Multi-language Support** - Internationalization
- **Advanced Analytics** - Detailed reporting
- **API Development** - RESTful API for mobile apps
- **Push Notifications** - Real-time updates
- **Social Media Integration** - Social sharing features

## 📄 License

This project is open source and available under the MIT License.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📞 Support

For support and questions:
- Email: support@qrmenu.com
- Documentation: [Link to docs]
- Issues: [GitHub Issues]

---

**Built with ❤️ for the restaurant industry** 