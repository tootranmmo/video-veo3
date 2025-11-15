# Installation Guide - Audit SEO Semantic

## Requirements

Before installing, ensure your server meets these requirements:
- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- Minimum 64MB PHP memory limit (128MB recommended)

## Installation Methods

### Method 1: Upload via WordPress Admin

1. Download the plugin zip file
2. Log in to your WordPress admin panel
3. Navigate to **Plugins > Add New**
4. Click **Upload Plugin** button
5. Choose the `audit-seo-semantic.zip` file
6. Click **Install Now**
7. After installation, click **Activate Plugin**

### Method 2: FTP/SFTP Upload

1. Extract the plugin zip file
2. Connect to your server via FTP/SFTP
3. Navigate to `/wp-content/plugins/`
4. Upload the `audit-seo-semantic` folder
5. Go to WordPress admin panel
6. Navigate to **Plugins**
7. Find "Audit SEO Semantic" and click **Activate**

### Method 3: WP-CLI

```bash
# Navigate to WordPress root directory
cd /path/to/wordpress

# Install the plugin
wp plugin install /path/to/audit-seo-semantic.zip

# Activate the plugin
wp plugin activate audit-seo-semantic
```

## First Time Setup

### 1. Database Tables

Upon activation, the plugin automatically creates two database tables:
- `wp_audit_seo_backlinks` - Stores backlink information
- `wp_audit_seo_history` - Stores audit history

No manual database setup is required.

### 2. Initial Configuration

1. Navigate to **SEO Audit > Settings**
2. Configure your preferences:
   - Enable/disable audit features
   - Set minimum content length (default: 300 words)
   - Set maximum keyword density (default: 3.5%)
   - Enable auto-audit on publish (optional)
3. Click **Save Settings**

### 3. Verify Installation

1. Go to **SEO Audit > Dashboard**
2. You should see the overview with statistics
3. Try running a test audit:
   - Go to **SEO Audit > Technical Audit**
   - Select a post/page
   - Click "Run Technical Audit"

## File Structure

```
audit-seo-semantic/
├── admin/
│   ├── class-admin.php
│   └── views/
│       ├── dashboard.php
│       ├── technical-audit.php
│       ├── backlink-audit.php
│       ├── content-audit.php
│       └── settings.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
├── includes/
│   ├── class-activator.php
│   ├── class-deactivator.php
│   ├── class-audit-seo-semantic.php
│   ├── class-technical-audit.php
│   ├── class-backlink-audit.php
│   └── class-content-audit.php
├── languages/
├── audit-seo-semantic.php (main plugin file)
├── uninstall.php
├── README.md
└── readme.txt
```

## Permissions

Ensure the following directories are writable:
- `/wp-content/plugins/audit-seo-semantic/` (for updates)
- `/wp-content/uploads/` (for future export features)

## Troubleshooting

### Plugin doesn't activate

**Solution:**
- Check PHP version (minimum 7.2)
- Check for PHP errors in debug.log
- Ensure no conflicts with other SEO plugins

### Database tables not created

**Solution:**
1. Deactivate the plugin
2. Check database user permissions
3. Re-activate the plugin
4. Check error logs

### AJAX requests fail

**Solution:**
- Clear browser cache
- Check JavaScript console for errors
- Ensure jQuery is loaded
- Verify WordPress AJAX URL is correct

### Styling issues

**Solution:**
- Hard refresh browser (Ctrl+F5 or Cmd+Shift+R)
- Clear WordPress cache if using caching plugin
- Check for theme conflicts

## Uninstallation

### Complete Removal

1. **Deactivate** the plugin from Plugins page
2. Click **Delete** to remove all plugin files
3. The uninstall script will automatically:
   - Drop database tables
   - Delete plugin options
   - Clear scheduled tasks
   - Remove post meta

### Keep Data (Manual Deactivation)

If you want to keep your audit data:
1. Only **Deactivate** the plugin
2. Don't delete it
3. Data will remain in database
4. Re-activate later to restore functionality

## Updating

### Automatic Updates

- Updates will appear in WordPress admin
- Click "Update Now" when available
- Plugin data will be preserved

### Manual Update

1. Deactivate the current version
2. Delete the old plugin folder via FTP
3. Upload the new version
4. Re-activate the plugin

**Note:** Always backup your database before updating!

## Database Backup

Before uninstalling or updating, backup these tables:
```sql
wp_audit_seo_backlinks
wp_audit_seo_history
wp_options (for plugin settings)
```

## Support

For installation issues:
1. Check error logs in `/wp-content/debug.log`
2. Enable WordPress debugging:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```
3. Contact support with error details

## Security Considerations

- The plugin uses WordPress nonces for AJAX security
- All inputs are sanitized and validated
- SQL queries use prepared statements
- No direct file access is allowed
- Follows WordPress coding standards

## Performance

The plugin is optimized for performance:
- Minimal database queries
- Efficient caching
- Asynchronous AJAX requests
- No impact on front-end performance
- Only loads admin assets on plugin pages

## Next Steps

After successful installation:
1. Run your first technical audit
2. Add backlinks to track
3. Analyze your content
4. Review dashboard statistics
5. Configure settings to your needs

Enjoy optimizing your WordPress site with Audit SEO Semantic!
